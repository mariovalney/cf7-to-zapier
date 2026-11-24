// The pages share the header and the docs menu, so the current page is marked here.
(function () {
    var path = window.location.pathname.replace(/\/$/, '') || '/';

    document.querySelectorAll('.site-nav a, .docs-nav a').forEach(function (link) {
        var href = link.getAttribute('href');
        var inSection = link.closest('.site-nav') && href === '/docs' && path.indexOf('/docs/') === 0;

        if (href === path || inSection) {
            link.setAttribute('aria-current', 'page');
        }
    });
})();
