import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../wayfinder'
import invoice18aa04 from './invoice'
/**
* @see \App\Http\Controllers\TransactionController::invoice
* @see app/Http/Controllers/TransactionController.php:570
* @route '/invoice/{uuid}'
*/
export const invoice = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoice.url(args, options),
    method: 'get',
})

invoice.definition = {
    methods: ["get","head"],
    url: '/invoice/{uuid}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransactionController::invoice
* @see app/Http/Controllers/TransactionController.php:570
* @route '/invoice/{uuid}'
*/
invoice.url = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { uuid: args }
    }

    if (Array.isArray(args)) {
        args = {
            uuid: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        uuid: args.uuid,
    }

    return invoice.definition.url
            .replace('{uuid}', parsedArgs.uuid.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::invoice
* @see app/Http/Controllers/TransactionController.php:570
* @route '/invoice/{uuid}'
*/
invoice.get = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: invoice.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::invoice
* @see app/Http/Controllers/TransactionController.php:570
* @route '/invoice/{uuid}'
*/
invoice.head = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: invoice.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransactionController::invoice
* @see app/Http/Controllers/TransactionController.php:570
* @route '/invoice/{uuid}'
*/
const invoiceForm = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoice.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::invoice
* @see app/Http/Controllers/TransactionController.php:570
* @route '/invoice/{uuid}'
*/
invoiceForm.get = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoice.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::invoice
* @see app/Http/Controllers/TransactionController.php:570
* @route '/invoice/{uuid}'
*/
invoiceForm.head = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: invoice.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

invoice.form = invoiceForm

const publicMethod = {
    invoice: Object.assign(invoice, invoice18aa04),
}

export default publicMethod