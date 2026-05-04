import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:398
* @route '/orders'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/orders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:398
* @route '/orders'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:398
* @route '/orders'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:398
* @route '/orders'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:398
* @route '/orders'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:398
* @route '/orders'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:398
* @route '/orders'
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
* @see \App\Http\Controllers\TransactionController::bulkStatus
* @see app/Http/Controllers/TransactionController.php:448
* @route '/orders/bulk-status'
*/
export const bulkStatus = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: bulkStatus.url(options),
    method: 'patch',
})

bulkStatus.definition = {
    methods: ["patch"],
    url: '/orders/bulk-status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TransactionController::bulkStatus
* @see app/Http/Controllers/TransactionController.php:448
* @route '/orders/bulk-status'
*/
bulkStatus.url = (options?: RouteQueryOptions) => {
    return bulkStatus.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::bulkStatus
* @see app/Http/Controllers/TransactionController.php:448
* @route '/orders/bulk-status'
*/
bulkStatus.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: bulkStatus.url(options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\TransactionController::bulkStatus
* @see app/Http/Controllers/TransactionController.php:448
* @route '/orders/bulk-status'
*/
const bulkStatusForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkStatus.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::bulkStatus
* @see app/Http/Controllers/TransactionController.php:448
* @route '/orders/bulk-status'
*/
bulkStatusForm.patch = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkStatus.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

bulkStatus.form = bulkStatusForm

const orders = {
    index: Object.assign(index, index),
    bulkStatus: Object.assign(bulkStatus, bulkStatus),
}

export default orders