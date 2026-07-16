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

// Keep the navigation link for the section currently under the header highlighted.
const sectionLinks = Array.from(document.querySelectorAll('.landing-nav-links a[href*="#"]'));
const trackedSections = sectionLinks.map(function (link) {
    const sectionId = new URL(link.href, window.location.href).hash.slice(1);
    return { link: link, section: document.getElementById(sectionId) };
}).filter(function (item) {
    return item.section;
});

function updateActiveSection() {
    const header = document.querySelector('.landing-header');
    const marker = (header ? header.offsetHeight : 0) + 40;
    let activeItem = null;

    trackedSections.forEach(function (item) {
        if (item.section.getBoundingClientRect().top <= marker) {
            activeItem = item;
        }
    });

    trackedSections.forEach(function (item) {
        const isActive = item === activeItem;
        item.link.classList.toggle('is-active', isActive);
        if (isActive) {
            item.link.setAttribute('aria-current', 'location');
        } else {
            item.link.removeAttribute('aria-current');
        }
    });
}

if (trackedSections.length > 0) {
    let scrollUpdatePending = false;
    function requestSectionUpdate() {
        if (scrollUpdatePending) return;
        scrollUpdatePending = true;
        window.requestAnimationFrame(function () {
            updateActiveSection();
            scrollUpdatePending = false;
        });
    }

    updateActiveSection();
    window.addEventListener('scroll', requestSectionUpdate, { passive: true });
    window.addEventListener('resize', requestSectionUpdate);
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
