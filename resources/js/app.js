import "./bootstrap";

// Khởi tạo Livewire (sẽ được load từ Laravel)
document.addEventListener("DOMContentLoaded", function () {
    // Livewire sẽ tự động khởi tạo khi có trong DOM
    if (window.Livewire) {
        console.log("Livewire đã được khởi tạo");
    }
});
