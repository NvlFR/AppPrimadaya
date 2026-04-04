import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:19
* @route '/stocks'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/stocks',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:19
* @route '/stocks'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:19
* @route '/stocks'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:19
* @route '/stocks'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:19
* @route '/stocks'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:19
* @route '/stocks'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::index
* @see app/Http/Controllers/StockController.php:19
* @route '/stocks'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\StockController::logs
* @see app/Http/Controllers/StockController.php:127
* @route '/stocks/logs'
*/
export const logs = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logs.url(options),
    method: 'get',
})

logs.definition = {
    methods: ["get","head"],
    url: '/stocks/logs',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StockController::logs
* @see app/Http/Controllers/StockController.php:127
* @route '/stocks/logs'
*/
logs.url = (options?: RouteQueryOptions) => {
    return logs.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StockController::logs
* @see app/Http/Controllers/StockController.php:127
* @route '/stocks/logs'
*/
logs.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: logs.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::logs
* @see app/Http/Controllers/StockController.php:127
* @route '/stocks/logs'
*/
logs.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: logs.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StockController::logs
* @see app/Http/Controllers/StockController.php:127
* @route '/stocks/logs'
*/
const logsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: logs.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::logs
* @see app/Http/Controllers/StockController.php:127
* @route '/stocks/logs'
*/
logsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: logs.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StockController::logs
* @see app/Http/Controllers/StockController.php:127
* @route '/stocks/logs'
*/
logsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: logs.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

logs.form = logsForm

/**
* @see \App\Http\Controllers\StockController::store
* @see app/Http/Controllers/StockController.php:49
* @route '/stocks'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/stocks',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\StockController::store
* @see app/Http/Controllers/StockController.php:49
* @route '/stocks'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StockController::store
* @see app/Http/Controllers/StockController.php:49
* @route '/stocks'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::store
* @see app/Http/Controllers/StockController.php:49
* @route '/stocks'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::store
* @see app/Http/Controllers/StockController.php:49
* @route '/stocks'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\StockController::update
* @see app/Http/Controllers/StockController.php:81
* @route '/stocks/{stock}'
*/
export const update = (args: { stock: number | { id: number } } | [stock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/stocks/{stock}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\StockController::update
* @see app/Http/Controllers/StockController.php:81
* @route '/stocks/{stock}'
*/
update.url = (args: { stock: number | { id: number } } | [stock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { stock: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { stock: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            stock: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        stock: typeof args.stock === 'object'
        ? args.stock.id
        : args.stock,
    }

    return update.definition.url
            .replace('{stock}', parsedArgs.stock.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StockController::update
* @see app/Http/Controllers/StockController.php:81
* @route '/stocks/{stock}'
*/
update.patch = (args: { stock: number | { id: number } } | [stock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\StockController::update
* @see app/Http/Controllers/StockController.php:81
* @route '/stocks/{stock}'
*/
const updateForm = (args: { stock: number | { id: number } } | [stock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StockController::update
* @see app/Http/Controllers/StockController.php:81
* @route '/stocks/{stock}'
*/
updateForm.patch = (args: { stock: number | { id: number } } | [stock: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const StockController = { index, logs, store, update }

export default StockController