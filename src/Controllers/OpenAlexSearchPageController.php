<?php

declare(strict_types=1);

namespace OpenPulse\Controllers;

use OpenPulse\Core\Response;
use OpenPulse\Core\Request;

final class OpenAlexSearchPageController
{
    public function index(Request $request): Response
    {
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenAlex Works Search | OpenPulse</title>
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: #f5f7fb;
            color: #1f2937;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI",
                sans-serif;
            line-height: 1.5;
        }

        main {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }

        h1 { margin-bottom: 6px; }
        .muted { color: #64748b; }

        form, .result, .toolbar {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 18px;
        }

        form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 14px;
            margin: 24px 0;
        }

        label {
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-size: 14px;
            font-weight: 600;
        }

        input, select, button {
            min-height: 40px;
            padding: 8px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font: inherit;
        }

        .actions {
            display: flex;
            align-items: end;
            gap: 8px;
        }

        button {
            cursor: pointer;
            background: #1d4ed8;
            color: white;
            border: 0;
        }

        button.secondary { background: #475569; }
        button:disabled { opacity: .5; cursor: not-allowed; }

        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }

        #results {
            display: grid;
            gap: 12px;
        }

        .result h2 {
            margin: 0 0 8px;
            font-size: 18px;
        }

        .result p { margin: 5px 0; }
        .result a { color: #1d4ed8; overflow-wrap: anywhere; }

        #status { margin: 16px 0; }
        #error { color: #b91c1c; }

        @media (max-width: 600px) {
            main { margin-top: 20px; }
            .toolbar { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
<main>
    <h1>OpenAlex Works Search</h1>
    <p class="muted">Search and explore the works stored in OpenPulse.</p>

    <form id="search-form">
        <label>
            Search title
            <input type="search" name="q" placeholder="Enter keywords">
        </label>

        <label>
            Publication year
            <input type="number" name="publication_year"
                min="1" max="9999" placeholder="e.g. 2024">
        </label>

        <label>
            Work type
            <input type="text" name="work_type"
                placeholder="e.g. article">
        </label>

        <label>
            Open access
            <select name="is_open_access">
                <option value="">Any</option>
                <option value="true">Open access</option>
                <option value="false">Not open access</option>
            </select>
        </label>

        <label>
            Sort by
            <select name="sort">
                <option value="id:desc">Newest imported ID</option>
                <option value="publication_year:desc">Publication year (newest)</option>
                <option value="publication_year:asc">Publication year (oldest)</option>
                <option value="cited_by_count:desc">Citations (highest)</option>
                <option value="title:asc">Title (A–Z)</option>
            </select>
        </label>

        <label>
            Results per page
            <select name="per_page">
                <option value="10">10</option>
                <option value="25" selected>25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
        </label>

        <div class="actions">
            <button type="submit">Search</button>
            <button type="reset" class="secondary">Reset</button>
        </div>
    </form>

    <div class="toolbar">
        <span id="summary">Ready to search.</span>
        <button id="next-page" type="button" disabled>Next page</button>
    </div>

    <p id="status" role="status"></p>
    <p id="error" role="alert"></p>
    <section id="results" aria-live="polite"></section>
</main>

<script>
(() => {
    const form = document.querySelector('#search-form');
    const results = document.querySelector('#results');
    const status = document.querySelector('#status');
    const error = document.querySelector('#error');
    const summary = document.querySelector('#summary');
    const nextButton = document.querySelector('#next-page');

    let nextCursor = null;
    let resultCount = 0;
    let requestVersion = 0;

    function addText(parent, tag, value, className = '') {
        const element = document.createElement(tag);
        element.textContent = value ?? 'N/A';

        if (className) {
            element.className = className;
        }

        parent.appendChild(element);
        return element;
    }

    function addLink(parent, url) {
        if (typeof url !== 'string' || !/^https?:\/\//i.test(url)) {
            return;
        }

        const link = document.createElement('a');
        link.href = url;
        link.target = '_blank';
        link.rel = 'noopener noreferrer';
        link.textContent = url;
        parent.appendChild(link);
    }

    function renderWorks(works) {
        results.replaceChildren();

        if (!Array.isArray(works) || works.length === 0) {
            addText(results, 'p', 'No works found.');
            return;
        }

        for (const work of works) {
            const card = document.createElement('article');
            card.className = 'result';

            addText(card, 'h2', work.title || 'Untitled work');
            addText(card, 'p', 'Year: ' + (work.publication_year ?? 'N/A'));
            addText(card, 'p', 'Type: ' + (work.work_type ?? 'N/A'));
            addText(card, 'p', 'Citations: ' + (work.cited_by_count ?? 0));
            addText(card, 'p', 'Open access: ' +
                (work.is_open_access == null
                    ? 'N/A'
                    : (work.is_open_access ? 'Yes' : 'No')));

            const linkContainer = document.createElement('p');
            addLink(linkContainer, work.openalex_url);
            card.appendChild(linkContainer);

            results.appendChild(card);
        }
    }

    async function search(append = false) {
        const version = ++requestVersion;

        error.textContent = '';
        status.textContent = 'Loading results...';
        nextButton.disabled = true;

        const params = new URLSearchParams();

        for (const [key, value] of new FormData(form).entries()) {
            if (typeof value === 'string' && value.trim() !== '') {
                params.set(key, value.trim());
            }
        }

        if (append && nextCursor) {
            params.set('cursor', nextCursor);
        }

        try {
            const response = await fetch(
                '/api/openalex/works?' + params.toString(),
                { headers: { 'Accept': 'application/json' } }
            );

            const payload = await response.json();

            if (version !== requestVersion) {
                return;
            }

            if (!response.ok || payload.error) {
                throw new Error(payload.error || 'Search request failed.');
            }

            const works = Array.isArray(payload.data) ? payload.data : [];

            if (append) {
                for (const work of works) {
                    const temporary = document.createElement('div');
                    renderSingleWork(temporary, work);
                    results.appendChild(temporary.firstElementChild);
                }
            } else {
                renderWorks(works);
            }

            resultCount = append ? resultCount + works.length : works.length;
            nextCursor = payload.next_cursor || null;

            summary.textContent = resultCount + ' results loaded';
            status.textContent = payload.has_more
                ? 'More results are available.'
                : 'End of results.';
            nextButton.disabled = !payload.has_more || !nextCursor;
        } catch (exception) {
            if (version !== requestVersion) {
                return;
            }

            status.textContent = '';
            error.textContent = exception.message;
        }
    }

    function renderSingleWork(parent, work) {
        const card = document.createElement('article');
        card.className = 'result';

        addText(card, 'h2', work.title || 'Untitled work');
        addText(card, 'p', 'Year: ' + (work.publication_year ?? 'N/A'));
        addText(card, 'p', 'Type: ' + (work.work_type ?? 'N/A'));
        addText(card, 'p', 'Citations: ' + (work.cited_by_count ?? 0));
        addText(card, 'p', 'Open access: ' +
            (work.is_open_access == null
                ? 'N/A'
                : (work.is_open_access ? 'Yes' : 'No')));

        const linkContainer = document.createElement('p');
        addLink(linkContainer, work.openalex_url);
        card.appendChild(linkContainer);

        parent.appendChild(card);
    }

    form.addEventListener('submit', event => {
        event.preventDefault();
        nextCursor = null;
        resultCount = 0;
        search(false);
    });

    form.addEventListener('reset', () => {
        window.setTimeout(() => {
            nextCursor = null;
            resultCount = 0;
            search(false);
        }, 0);
    });

    nextButton.addEventListener('click', () => {
        if (nextCursor) {
            search(true);
        }
    });

    search(false);
})();
</script>
</body>
</html>
HTML;

        return Response::html($html);
    }
}
