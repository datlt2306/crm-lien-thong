@vite(['resources/css/app.css', 'resources/js/app.js'])

<style>
    /* Đồng bộ Tabs ngang hàng với Search trong Table */
    .fi-ta-ctn {
        display: grid !important;
        grid-template-areas: 
            "header"
            "toolbar"
            "content" !important;
    }

    .fi-ta-header { grid-area: header; }

    /* Ép Tabs và Toolbar (Search) vào cùng một vùng 'toolbar' */
    .fi-ta-ctn > .fi-tabs {
        grid-area: toolbar;
        z-index: 5;
        width: fit-content;
        background-color: transparent !important;
        border-bottom: none !important;
        margin-bottom: 0 !important;
        padding: 0.5rem 0 0.5rem 1rem !important;
        position: relative;
    }

    .fi-ta-header-toolbar {
        grid-area: toolbar;
        justify-self: end;
        width: 100%;
        display: flex !important;
        align-items: center !important;
        justify-content: flex-end !important;
    }

    /* Biến Tab thành dạng Button Segmented */
    .fi-tabs-item {
        padding-top: 0.4rem !important;
        padding-bottom: 0.4rem !important;
        border-radius: 0.5rem !important;
        transition: all 0.2s;
        font-size: 0.85rem !important;
        margin-right: 0.25rem;
        background-color: rgba(var(--gray-500), 0.05) !important;
        border: 1px solid rgba(var(--gray-500), 0.1) !important;
    }

    .fi-tabs-item-active {
        background-color: rgba(var(--primary-600), 0.1) !important;
        color: rgb(var(--primary-600)) !important;
        border: 1px solid rgb(var(--primary-600)) !important;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    .fi-tabs-item:hover:not(.fi-tabs-item-active) {
        background-color: rgba(var(--gray-500), 0.1) !important;
    }

    /* Đảm bảo thanh search không bị che */
    .fi-ta-search-input-ctn {
        margin-left: 1rem;
    }

    @media (max-width: 768px) {
        .notification-dropdown {
            width: 18rem !important;
            right: -1rem !important;
        }
    }
</style>