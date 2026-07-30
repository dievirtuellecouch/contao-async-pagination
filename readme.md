# Async Pagination

Adds wrapper around paginated frontend modules for asynchronous reloading.

## Configuration

Customize the bundle using the `async_pagination` root node.

### Target Frontend Module Types

By default, nested modules of type `list`, `archive` and `reader` can be reloaded. The extension checks if the module type contains one of those keywords, so that all lists and readers (e. g. `newslist` and `faqlist`) are supported out of the box.

To add additional types to your app, use the following setting:

```yaml
async_pagination:
    target_frontend_model_types: ['my_custom_frontend_module_type']
```

#### Shared Max Age

Async responses of the endpoint are cached for 1 hour by default. To change the behaviour, use the following setting. Disable caching by setting the value to `null` or use another number, defined in seconds.

```yaml
async_pagination:
    shared_max_age: 60
```
