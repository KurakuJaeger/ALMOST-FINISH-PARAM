const menuButton = document.querySelector('.landing-menu-button');
const menuPanel = document.querySelector('.landing-menu-panel');

if (menuButton && menuPanel) {
    menuButton.addEventListener('click', function () {
        const isOpen = menuPanel.classList.toggle('open');
        menuButton.setAttribute('aria-expanded', String(isOpen));
    });

    menuPanel.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            menuPanel.classList.remove('open');
            menuButton.setAttribute('aria-expanded', 'false');
        });
    });
}

const revealItems = document.querySelectorAll('.reveal');

if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12 });

    revealItems.forEach(function (item) {
        observer.observe(item);
    });
} else {
    revealItems.forEach(function (item) {
        item.classList.add('visible');
    });
}
