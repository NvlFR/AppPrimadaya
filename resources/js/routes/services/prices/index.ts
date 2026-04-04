import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\ServiceController::store
* @see app/Http/Controllers/ServiceController.php:114
* @route '/services/{service}/prices'
*/
export const store = (args: { service: string | number | { id: string | number } } | [service: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/services/{service}/prices',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\ServiceController::store
* @see app/Http/Controllers/ServiceController.php:114
* @route '/services/{service}/prices'
*/
store.url = (args: { service: string | number | { id: string | number } } | [service: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { service: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { service: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            service: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        service: typeof args.service === 'object'
        ? args.service.id
        : args.service,
    }

    return store.definition.url
            .replace('{service}', parsedArgs.service.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\ServiceController::store
* @see app/Http/Controllers/ServiceController.php:114
* @route '/services/{service}/prices'
*/
store.post = (args: { service: string | number | { id: string | number } } | [service: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ServiceController::store
* @see app/Http/Controllers/ServiceController.php:114
* @route '/services/{service}/prices'
*/
const storeForm = (args: { service: string | number | { id: string | number } } | [service: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\ServiceController::store
* @see app/Http/Controllers/ServiceController.php:114
* @route '/services/{service}/prices'
*/
storeForm.post = (args: { service: string | number | { id: string | number } } | [service: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(args, options),
    method: 'post',
})

store.form = storeForm

const prices = {
    store: Object.assign(store, store),
}

export default prices