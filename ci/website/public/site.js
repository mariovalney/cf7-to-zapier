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

// Docs headings with an id link to their section, and a click also copies that address.
(function () {
    var status = document.createElement('p');
    status.className = 'visually-hidden';
    status.setAttribute('aria-live', 'polite');
    document.body.appendChild(status);

    document.querySelectorAll('.content h2[id], .content h3[id]').forEach(function (heading) {
        var link = document.createElement('a');
        var timer;

        link.className = 'heading-link';
        link.href = '#' + heading.id;

        while (heading.firstChild) {
            link.appendChild(heading.firstChild);
        }

        heading.appendChild(link);

        link.addEventListener('click', function () {
            if (!navigator.clipboard) {
                return;
            }

            navigator.clipboard.writeText(window.location.origin + window.location.pathname + '#' + heading.id).then(function () {
                clearTimeout(timer);
                link.classList.add('copied');
                status.textContent = 'Link copied';

                timer = setTimeout(function () {
                    link.classList.remove('copied');
                    status.textContent = '';
                }, 2000);
            });
        });
    });
})();
