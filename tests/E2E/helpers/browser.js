const puppeteer = require("puppeteer");
const fs = require("fs").promises;
const path = require("path");
const config = require("../config");

class BrowserHelper {
    constructor() {
        this.browser = null;
        this.page = null;
        this.screenshotCounter = 0;
        this.testResults = [];
    }

    async init() {
        console.log("🚀 Khởi tạo browser...");
        try {
            this.browser = await puppeteer.launch({
                headless: true, // Chạy ẩn để tránh lỗi GUI
                defaultViewport: { width: 1280, height: 720 },
                args: [
                    "--no-sandbox",
                    "--disable-setuid-sandbox",
                    "--disable-dev-shm-usage",
                    "--disable-accelerated-2d-canvas",
                    "--no-first-run",
                    "--no-zygote",
                    "--disable-gpu",
                    "--disable-web-security",
                    "--disable-features=VizDisplayCompositor",
                ],
                timeout: 30000,
                protocolTimeout: 60000,
            });
            this.page = await this.browser.newPage();
        } catch (error) {
            console.error("❌ Lỗi khởi tạo browser:", error.message);
            throw error;
        }

        // Set timeouts
        this.page.setDefaultTimeout(config.timeouts.elementWait);
        this.page.setDefaultNavigationTimeout(config.timeouts.pageLoad);

        console.log("✅ Browser đã sẵn sàng");
    }

    async close() {
        if (this.browser) {
            await this.browser.close();
            console.log("🔒 Browser đã đóng");
        }
    }

    async navigate(url) {
        const fullUrl = url.startsWith("http")
            ? url
            : `${config.baseUrl}${url}`;
        console.log(`📍 Điều hướng đến: ${fullUrl}`);

        try {
            // Kiểm tra xem page có còn hoạt động không
            if (this.page.isClosed()) {
                throw new Error("Page đã bị đóng");
            }

            await this.page.goto(fullUrl, {
                waitUntil: "domcontentloaded",
                timeout: config.timeouts.pageLoad,
            });

            // Đợi thêm một chút để trang load hoàn toàn
            await this.sleep(2000);
            await this.waitForPageLoad();
            return true;
        } catch (error) {
            console.error(`❌ Lỗi điều hướng đến ${fullUrl}:`, error.message);
            await this.captureScreenshot(`navigation_error_${Date.now()}`);
            throw error;
        }
    }

    async waitForPageLoad() {
        try {
            await this.page.waitForSelector("body", { timeout: 5000 });
            // Đợi thêm một chút để các script load xong
            await this.page.waitForTimeout(1000);
        } catch (error) {
            console.warn("⚠️ Timeout chờ page load, tiếp tục...");
        }
    }

    async login(account) {
        console.log(`🔐 Đăng nhập với tài khoản: ${account.email}`);

        try {
            await this.navigate(config.adminPanel);

            // Đợi form login xuất hiện với timeout dài hơn
            await this.waitForElement('input[id="form.email"]', 15000);

            // Đợi Livewire load hoàn toàn
            await this.sleep(2000);

            // Clear existing values and fill login form
            await this.page.click('input[id="form.email"]', { clickCount: 3 });
            await this.page.keyboard.press("Backspace");
            await this.page.type('input[id="form.email"]', account.email);

            await this.page.click('input[id="form.password"]', {
                clickCount: 3,
            });
            await this.page.keyboard.press("Backspace");
            await this.page.type('input[id="form.password"]', account.password);

            // Đợi một chút để form được điền đầy đủ
            await this.sleep(2000);

            // Click nút đăng nhập
            await this.clickElement('button[type="submit"]');

            // Đợi Livewire xử lý - đợi button không còn loading
            try {
                await this.page.waitForFunction(
                    () => {
                        const button = document.querySelector(
                            'button[type="submit"]'
                        );
                        return (
                            button &&
                            !button.disabled &&
                            !button.classList.contains("fi-processing")
                        );
                    },
                    { timeout: 10000 }
                );
            } catch (e) {
                console.log("⚠️ Timeout chờ Livewire, tiếp tục...");
            }

            // Đợi thêm một chút để đảm bảo
            await this.sleep(2000);

            // Kiểm tra xem có lỗi đăng nhập không
            const errorElements = await this.page.$$(
                ".fi-fo-field-error-message, .fi-notification-danger, .text-red-500, .fi-alert-danger"
            );
            if (errorElements.length > 0) {
                const errorText = await this.page.evaluate(() => {
                    const errors = document.querySelectorAll(
                        ".fi-fo-field-error-message, .fi-notification-danger, .text-red-500, .fi-alert-danger"
                    );
                    return Array.from(errors)
                        .map((el) => el.textContent)
                        .join(", ");
                });
                throw new Error(`Đăng nhập thất bại: ${errorText}`);
            }

            // Kiểm tra đăng nhập thành công
            const currentUrl = this.page.url();
            if (
                currentUrl.includes("/admin") &&
                !currentUrl.includes("/login")
            ) {
                console.log("✅ Đăng nhập thành công");
                await this.captureScreenshot(
                    `login_success_${account.email.split("@")[0]}`
                );
                return true;
            } else {
                // Nếu vẫn ở trang login, có thể cần đợi thêm
                await this.sleep(3000);
                const finalUrl = this.page.url();
                if (
                    finalUrl.includes("/admin") &&
                    !finalUrl.includes("/login")
                ) {
                    console.log("✅ Đăng nhập thành công (sau delay)");
                    return true;
                }

                // Nếu vẫn thất bại, lấy thông tin debug
                const pageContent = await this.page.content();
                const hasError =
                    pageContent.includes("error") ||
                    pageContent.includes("Error");
                throw new Error(
                    `Đăng nhập thất bại - URL: ${finalUrl}, Has Error: ${hasError}`
                );
            }
        } catch (error) {
            console.error(`❌ Lỗi đăng nhập:`, error.message);
            await this.captureScreenshot(
                `login_error_${account.email.split("@")[0]}`
            );
            throw error;
        }
    }

    async logout() {
        console.log("🚪 Đăng xuất...");

        try {
            // Tìm và click nút user menu
            const userMenuSelector =
                '[data-testid="user-menu"], .fi-topbar-user-menu, [aria-label*="user"], [aria-label*="account"]';
            await this.waitForElement(userMenuSelector, 5000);
            await this.clickElement(userMenuSelector);

            // Tìm và click nút logout
            const logoutSelector =
                'a[href*="logout"], button:contains("Đăng xuất"), button:contains("Logout")';
            await this.waitForElement(logoutSelector, 3000);
            await this.clickElement(logoutSelector);

            // Đợi redirect về trang login
            await this.page.waitForNavigation({ waitUntil: "networkidle2" });
            console.log("✅ Đăng xuất thành công");
        } catch (error) {
            console.warn("⚠️ Không thể đăng xuất tự động, tiếp tục...");
            // Thử cách khác - clear session
            await this.page.evaluate(() => {
                localStorage.clear();
                sessionStorage.clear();
            });
        }
    }

    async waitForElement(selector, timeout = config.timeouts.elementWait) {
        try {
            await this.page.waitForSelector(selector, { timeout });
            return true;
        } catch (error) {
            console.error(`❌ Không tìm thấy element: ${selector}`);
            await this.captureScreenshot(
                `missing_element_${selector.replace(/[^a-zA-Z0-9]/g, "_")}`
            );
            throw error;
        }
    }

    async clickElement(selector, options = {}) {
        try {
            await this.waitForElement(selector);
            await this.page.click(selector, options);
            await this.page.waitForTimeout(config.timeouts.actionWait);
            console.log(`🖱️ Đã click: ${selector}`);
            return true;
        } catch (error) {
            console.error(`❌ Lỗi click element: ${selector}`, error.message);
            await this.captureScreenshot(
                `click_error_${selector.replace(/[^a-zA-Z0-9]/g, "_")}`
            );
            throw error;
        }
    }

    async typeText(selector, text) {
        try {
            await this.waitForElement(selector);
            await this.page.click(selector); // Focus vào input
            await this.page.keyboard.type(text);
            console.log(`⌨️ Đã nhập text: ${text} vào ${selector}`);
            return true;
        } catch (error) {
            console.error(`❌ Lỗi nhập text: ${selector}`, error.message);
            await this.captureScreenshot(
                `type_error_${selector.replace(/[^a-zA-Z0-9]/g, "_")}`
            );
            throw error;
        }
    }

    async selectOption(selector, value) {
        try {
            await this.waitForElement(selector);
            await this.page.select(selector, value);
            console.log(`📋 Đã chọn option: ${value} từ ${selector}`);
            return true;
        } catch (error) {
            console.error(`❌ Lỗi chọn option: ${selector}`, error.message);
            await this.captureScreenshot(
                `select_error_${selector.replace(/[^a-zA-Z0-9]/g, "_")}`
            );
            throw error;
        }
    }

    async uploadFile(selector, filePath) {
        try {
            await this.waitForElement(selector);
            const input = await this.page.$(selector);
            await input.uploadFile(filePath);
            console.log(`📁 Đã upload file: ${filePath} vào ${selector}`);
            return true;
        } catch (error) {
            console.error(`❌ Lỗi upload file: ${selector}`, error.message);
            await this.captureScreenshot(
                `upload_error_${selector.replace(/[^a-zA-Z0-9]/g, "_")}`
            );
            throw error;
        }
    }

    async getText(selector) {
        try {
            await this.waitForElement(selector);
            const text = await this.page.$eval(selector, (el) =>
                el.textContent.trim()
            );
            return text;
        } catch (error) {
            console.error(
                `❌ Không thể lấy text từ: ${selector}`,
                error.message
            );
            return null;
        }
    }

    async getValue(selector) {
        try {
            await this.waitForElement(selector);
            const value = await this.page.$eval(selector, (el) => el.value);
            return value;
        } catch (error) {
            console.error(
                `❌ Không thể lấy value từ: ${selector}`,
                error.message
            );
            return null;
        }
    }

    async isElementVisible(selector) {
        try {
            const element = await this.page.$(selector);
            if (!element) return false;

            const isVisible = await this.page.evaluate((el) => {
                const style = window.getComputedStyle(el);
                return (
                    style.display !== "none" &&
                    style.visibility !== "hidden" &&
                    style.opacity !== "0"
                );
            }, element);

            return isVisible;
        } catch (error) {
            return false;
        }
    }

    async waitForText(text, timeout = config.timeouts.elementWait) {
        try {
            await this.page.waitForFunction(
                (searchText) => document.body.textContent.includes(searchText),
                { timeout },
                text
            );
            console.log(`✅ Tìm thấy text: ${text}`);
            return true;
        } catch (error) {
            console.error(`❌ Không tìm thấy text: ${text}`);
            await this.captureScreenshot(
                `missing_text_${text.replace(/[^a-zA-Z0-9]/g, "_")}`
            );
            throw error;
        }
    }

    async captureScreenshot(name) {
        if (!config.screenshots.enabled) return;

        try {
            // Tạo thư mục screenshots nếu chưa có
            const screenshotDir = path.join(__dirname, "..", "screenshots");
            await fs.mkdir(screenshotDir, { recursive: true });

            // Tạo tên file với timestamp
            const timestamp = new Date().toISOString().replace(/[:.]/g, "-");
            const filename = `${timestamp}_${name}.${config.screenshots.format}`;
            const filepath = path.join(screenshotDir, filename);

            await this.page.screenshot({
                path: filepath,
                fullPage: true,
            });

            console.log(`📸 Screenshot saved: ${filename}`);
            return filepath;
        } catch (error) {
            console.error("❌ Lỗi chụp screenshot:", error.message);
        }
    }

    async assertElementExists(selector, message) {
        const exists = await this.isElementVisible(selector);
        if (!exists) {
            await this.captureScreenshot(
                `assert_failed_${selector.replace(/[^a-zA-Z0-9]/g, "_")}`
            );
            throw new Error(message || `Element không tồn tại: ${selector}`);
        }
        console.log(`✅ Assert passed: ${message || selector} tồn tại`);
    }

    async assertTextContains(selector, expectedText, message) {
        const actualText = await this.getText(selector);
        if (!actualText || !actualText.includes(expectedText)) {
            await this.captureScreenshot(
                `assert_failed_text_${expectedText.replace(
                    /[^a-zA-Z0-9]/g,
                    "_"
                )}`
            );
            throw new Error(
                message ||
                    `Text không khớp. Expected: ${expectedText}, Actual: ${actualText}`
            );
        }
        console.log(`✅ Assert passed: Text chứa "${expectedText}"`);
    }

    async assertUrlContains(expectedUrl, message) {
        const currentUrl = this.page.url();
        if (!currentUrl.includes(expectedUrl)) {
            await this.captureScreenshot(
                `assert_failed_url_${expectedUrl.replace(/[^a-zA-Z0-9]/g, "_")}`
            );
            throw new Error(
                message ||
                    `URL không khớp. Expected: ${expectedUrl}, Actual: ${currentUrl}`
            );
        }
        console.log(`✅ Assert passed: URL chứa "${expectedUrl}"`);
    }

    // Helper để tìm element với nhiều selector khả năng
    async findElementBySelectors(selectors) {
        for (const selector of selectors) {
            try {
                await this.waitForElement(selector, 2000);
                return selector;
            } catch (error) {
                continue;
            }
        }
        throw new Error(
            `Không tìm thấy element với các selector: ${selectors.join(", ")}`
        );
    }

    // Helper để đợi và click element với nhiều selector khả năng
    async clickBySelectors(selectors) {
        const selector = await this.findElementBySelectors(selectors);
        return await this.clickElement(selector);
    }

    // Helper để đợi
    async sleep(ms) {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }
}

module.exports = BrowserHelper;
