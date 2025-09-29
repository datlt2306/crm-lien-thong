@vite(['resources/css/app.css', 'resources/js/app.js'])

<style>
    /* Custom notification bell styles */
    .notification-badge {
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-red {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    /* Notification dropdown responsive */
    @media (max-width: 768px) {
        .notification-dropdown {
            width: 18rem;
            right: -1rem;
        }
    }
</style>