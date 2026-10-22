const documentReady = function() {
    const items = document.querySelectorAll('[data-module="pagination-reload"]');

    for (const item of items) {
        new ListReload(item);
    }
};

const ListReload = function (container) {
    this.name = 'ListReload';
    this.elements = {
        container: null,
        list: null,
        listContainer: null,
        filterContainer: null,
        filterItems: [],
    };
    this.config = {
    };
    this.state = {
        isLoading: false,
        currentFilter: {},
    };

    let self = this;

    this.load = async function(filter) {
        const params = new URLSearchParams(filter);
        params.forEach((value, key) => {
            if (value === 'null') {
                params.delete(key);
            }
        });

        params.set('_dvc_page', window.location.pathname + window.location.search);

        const endpointType = self.elements.targetContainer.getAttribute('data-type');
        const endpointId = self.elements.targetContainer.getAttribute('data-id');
        const endpoint = self.elements.targetContainer.getAttribute('data-endpoint')
            ?? `/_dvc/ajax/${endpointType}/${endpointId}`;

        try {
            self.setLoadingState(true);

            const request = await fetch(`${endpoint}?${params.toString()}`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (request.status !== 200) {
                return;
            }

            const result = await request.json();

            if (typeof result.html === 'undefined') {
                return;
            }

            self.state.currentFilter = filter;

            self.render();
            const replaced = await self.transition(self.replace.bind(self, result.html));

            if (replaced && self.config.scrollToTop) {
                const scrollTarget = self.elements.filterContainer ?? self.elements.targetContainer;
                scrollTarget.scrollIntoView({
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
                    block: 'start',
                });
            }
        }
        catch (error) {
            console.error(error);
        }
        finally {
            self.setLoadingState(false);
        }
    };

    this.setLoadingState = function(newState) {
        self.state.isLoading = newState;
    }

    this.transition = async function(callback) {
        if (!document.startViewTransition || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return callback();
        }

        let replaced = false;
        const transition = document.startViewTransition(() => {
            replaced = callback();
        });

        await transition.updateCallbackDone;
        // A skipped animation still applies the DOM update. Scroll only once
        // its snapshots no longer cover the newly rendered list.
        await transition.finished.catch(() => {});

        return replaced;
    };

    this.render = function() {
        for (const item of self.elements.filterItems) {
            const [filterKey, filterValue] = self.getFilterFromElement(item);

            const isActive = (() => {
                if (Object.keys(self.state.currentFilter).indexOf(filterKey) < 0 && filterValue === null) {
                    return true;
                }

                return self.state.currentFilter[filterKey] === filterValue;
            })();

            item.setAttribute('data-active', isActive);
        }
    };

    this.replace = function(newContent) {
        const replaceElement = self.getReloadElement();
        const parent = replaceElement.parentElement;
        const template = document.createElement('template');
        template.innerHTML = newContent;

        const newElement = template.content.children.length === 1 ? template.content.children[0] : null;

        if (newElement === null) {
            return false;
        }

        parent.replaceChild(newElement, replaceElement);
        self.elements.target = newElement;
        return true;
    };

    this.onFilterChange = function(event, targetElement = event.currentTarget) {
        event.preventDefault();

        let filter = {};
        let newFilterKey = null;
        let newFilterValue = null;
        let newFilter = {};

        switch (targetElement.tagName) {
            case 'A':
                const targetHref = new URL(targetElement.href);

                if (targetHref.searchParams.size > 0) {
                    filter = Object.assign({}, filter, Object.fromEntries(targetHref.searchParams.entries()));
                }

                [newFilterKey, newFilterValue] = self.getFilterFromElement(targetElement);

                break;

            case 'SELECT':
                [newFilterKey, newFilterValue] = self.getFilterFromInput(targetElement);

                break;

            default:
                break;
        }

        if (newFilterKey !== null) {
            newFilter[newFilterKey] = newFilterValue;
        }

        filter = Object.assign(filter, newFilter);

        return self.load(filter);
    };

    this.getReloadElement = function() {
        return this.elements.target;
    }

    this.getFilterFromElement = function(element) {
        const filterKey = element.getAttribute('data-pagination-filter-key');
        let filterValue = element.getAttribute('data-pagination-filter-value');

        return [filterKey, filterValue];
    }

    this.getFilterFromInput = function(element) {
        const filterKey = element.getAttribute('data-pagination-filter-key');
        let filterValue = element.value;

        if (filterValue === '') {
            filterValue = null;
        }

        return [filterKey, filterValue];
    }

    this.initInnerEventListener = function() {
        // The wrapper survives replacements. Delegation also handles clicks
        // immediately after a page swap, before the next animation frame.
        self.elements.container.addEventListener('click', (event) => {
            const link = event.target instanceof Element ? event.target.closest('.pagination a') : null;

            if (link?.closest('[data-module="pagination-reload"]') === self.elements.container) {
                self.onFilterChange(event, link);
            }
        });
    };

    this.init = function() {
        self.elements.container = container;
        self.config.scrollToTop = container.getAttribute('data-scroll-to-top') === 'true';
        self.elements.targetContainer = container.querySelector(`[data-element="target"]`);

        if (self.elements.targetContainer === null) {
            return;
        }

        self.elements.target = self.elements.targetContainer.children[0];

        self.elements.filterContainer = container.querySelector('[data-element="filter"]');
        self.elements.filterItems = self.elements.filterContainer?.querySelectorAll('a') ?? [];
        self.elements.filterSelect = self.elements.filterContainer?.querySelector('select') ?? null;

        self.initInnerEventListener();

        for (const item of self.elements.filterItems) {
            item.addEventListener('click', self.onFilterChange);

            if (item.getAttribute('data-active') === 'true') {
                self.state.currentFilter = Object.assign(self.state.currentFilter, self.getFilterFromElement(item));
            }
        }

        if (self.elements.filterSelect !== null) {
            self.elements.filterSelect.addEventListener('change', self.onFilterChange);
        }
    }();
};

documentReady(function() {
    const items = document.querySelectorAll('[data-module="pagination-reload"]');

    for (const item of items) {
        new ListReload(item);
    }
});
