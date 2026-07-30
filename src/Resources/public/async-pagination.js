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

        const endpointType = self.elements.targetContainer.getAttribute('data-type');
        const endpointId = self.elements.targetContainer.getAttribute('data-id');

        try {
            self.setLoadingState(true);

            const request = await fetch(`/_dvc/ajax/${endpointType}/${endpointId}?${params.toString()}`, {
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
            self.transition(self.replace.bind(self, result.html));
            self.setLoadingState(false);
        }
        catch (error) {
            console.error(error);
        }
    };

    this.setLoadingState = function(newState) {
        self.state.isLoading = newState;
    }

    this.transition = function(callback) {
        if (!document.startViewTransition) {
            callback();
            return;
        }

        document.startViewTransition(() => callback());
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
            return;
        }

        parent.replaceChild(newElement, replaceElement);

        window.requestAnimationFrame(() => {
            self.elements.target = newElement;

            self.initInnerEventListener();
        });
    };

    this.onFilterChange = function(event) {
        event.preventDefault();

        const targetElement = event.currentTarget;
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

        self.load(filter);
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
        const innerElements = self.elements.container.querySelectorAll('.pagination a');

        for (const link of innerElements) {
            link.addEventListener('click', self.onFilterChange);
        }
    };

    this.init = function() {
        self.elements.container = container;
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
