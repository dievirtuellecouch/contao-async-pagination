const AsyncLoad = function (container) {
    this.name = 'AsyncLoad';
    this.elements = {
        container: null,
        target: null,
    };
    this.config = {
    };
    this.state = {
        isLoading: false,
    };

    let self = this;

    this.load = async function() {
        const params = new URLSearchParams({
            'uncached': true,
        });

        const endpointType = self.elements.target.getAttribute('data-type');
        const endpointId = self.elements.target.getAttribute('data-id');

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

            self.replace(result.html);
            self.setLoadingState(false);
        }
        catch (error) {
            console.error(error);
        }
    };

    this.setLoadingState = function(newState) {
        self.state.isLoading = newState;
    }

    this.replace = function(newContent) {
        const replaceElement = this.elements.target;
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
        });
    };

    this.init = function() {
        self.elements.container = container;
        self.elements.target = container.querySelector(`[data-element="target"]`);

        if (self.elements.target === null) {
            return;
        }

        self.load();
    }();
};

const items = document.querySelectorAll('[data-module="async-load"]');

for (const item of items) {
    new AsyncLoad(item);
}
