import './bootstrap';
import './mega-upload';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-kk-moderation-root]').forEach((root) => {
        const toggle = root.querySelector('[data-kk-moderation-toggle]');
        const menu = root.querySelector('[data-kk-moderation-menu]');
        if (!toggle || !menu) {
            return;
        }

        const setOpen = (open) => {
            menu.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        };

        setOpen(false);

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            setOpen(menu.hidden);
        });

        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) {
                setOpen(false);
            }
        });
    });

    document.querySelectorAll('[data-kk-mobile-nav-root]').forEach((root) => {
        const toggle = root.querySelector('[data-kk-mobile-nav-toggle]');
        const menu = root.querySelector('[data-kk-mobile-nav-menu]');
        if (!toggle || !menu) {
            return;
        }

        const iconClosed = root.querySelector('[data-kk-mobile-nav-icon="closed"]');
        const iconOpen = root.querySelector('[data-kk-mobile-nav-icon="open"]');

        const setOpen = (open) => {
            menu.classList.toggle('hidden', !open);
            menu.classList.toggle('block', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (iconClosed) {
                iconClosed.classList.toggle('hidden', open);
                iconClosed.classList.toggle('inline-flex', !open);
            }
            if (iconOpen) {
                iconOpen.classList.toggle('hidden', !open);
                iconOpen.classList.toggle('inline-flex', open);
            }
        };

        setOpen(false);

        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            setOpen(menu.classList.contains('hidden'));
        });
    });
});
