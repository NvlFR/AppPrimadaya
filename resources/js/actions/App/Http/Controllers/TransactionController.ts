import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:27
* @route '/transactions'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/transactions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:27
* @route '/transactions'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:27
* @route '/transactions'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:27
* @route '/transactions'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:27
* @route '/transactions'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:27
* @route '/transactions'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::index
* @see app/Http/Controllers/TransactionController.php:27
* @route '/transactions'
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
* @see \App\Http\Controllers\TransactionController::create
* @see app/Http/Controllers/TransactionController.php:76
* @route '/transactions/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/transactions/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransactionController::create
* @see app/Http/Controllers/TransactionController.php:76
* @route '/transactions/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::create
* @see app/Http/Controllers/TransactionController.php:76
* @route '/transactions/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::create
* @see app/Http/Controllers/TransactionController.php:76
* @route '/transactions/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransactionController::create
* @see app/Http/Controllers/TransactionController.php:76
* @route '/transactions/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::create
* @see app/Http/Controllers/TransactionController.php:76
* @route '/transactions/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::create
* @see app/Http/Controllers/TransactionController.php:76
* @route '/transactions/create'
*/
createForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

create.form = createForm

/**
* @see \App\Http\Controllers\TransactionController::store
* @see app/Http/Controllers/TransactionController.php:111
* @route '/transactions'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/transactions',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransactionController::store
* @see app/Http/Controllers/TransactionController.php:111
* @route '/transactions'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::store
* @see app/Http/Controllers/TransactionController.php:111
* @route '/transactions'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::store
* @see app/Http/Controllers/TransactionController.php:111
* @route '/transactions'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::store
* @see app/Http/Controllers/TransactionController.php:111
* @route '/transactions'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\TransactionController::show
* @see app/Http/Controllers/TransactionController.php:267
* @route '/transactions/{transaction}'
*/
export const show = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/transactions/{transaction}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransactionController::show
* @see app/Http/Controllers/TransactionController.php:267
* @route '/transactions/{transaction}'
*/
show.url = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { transaction: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            transaction: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transaction: typeof args.transaction === 'object'
        ? args.transaction.id
        : args.transaction,
    }

    return show.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::show
* @see app/Http/Controllers/TransactionController.php:267
* @route '/transactions/{transaction}'
*/
show.get = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::show
* @see app/Http/Controllers/TransactionController.php:267
* @route '/transactions/{transaction}'
*/
show.head = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransactionController::show
* @see app/Http/Controllers/TransactionController.php:267
* @route '/transactions/{transaction}'
*/
const showForm = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::show
* @see app/Http/Controllers/TransactionController.php:267
* @route '/transactions/{transaction}'
*/
showForm.get = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::show
* @see app/Http/Controllers/TransactionController.php:267
* @route '/transactions/{transaction}'
*/
showForm.head = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

show.form = showForm

/**
* @see \App\Http\Controllers\TransactionController::updateStatus
* @see app/Http/Controllers/TransactionController.php:398
* @route '/transactions/{transaction}/status'
*/
export const updateStatus = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateStatus.url(args, options),
    method: 'patch',
})

updateStatus.definition = {
    methods: ["patch"],
    url: '/transactions/{transaction}/status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TransactionController::updateStatus
* @see app/Http/Controllers/TransactionController.php:398
* @route '/transactions/{transaction}/status'
*/
updateStatus.url = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { transaction: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            transaction: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transaction: typeof args.transaction === 'object'
        ? args.transaction.id
        : args.transaction,
    }

    return updateStatus.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::updateStatus
* @see app/Http/Controllers/TransactionController.php:398
* @route '/transactions/{transaction}/status'
*/
updateStatus.patch = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: updateStatus.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\TransactionController::updateStatus
* @see app/Http/Controllers/TransactionController.php:398
* @route '/transactions/{transaction}/status'
*/
const updateStatusForm = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStatus.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::updateStatus
* @see app/Http/Controllers/TransactionController.php:398
* @route '/transactions/{transaction}/status'
*/
updateStatusForm.patch = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateStatus.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateStatus.form = updateStatusForm

/**
* @see \App\Http\Controllers\TransactionController::processPayment
* @see app/Http/Controllers/TransactionController.php:326
* @route '/transactions/{transaction}/payment'
*/
export const processPayment = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processPayment.url(args, options),
    method: 'post',
})

processPayment.definition = {
    methods: ["post"],
    url: '/transactions/{transaction}/payment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TransactionController::processPayment
* @see app/Http/Controllers/TransactionController.php:326
* @route '/transactions/{transaction}/payment'
*/
processPayment.url = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { transaction: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            transaction: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transaction: typeof args.transaction === 'object'
        ? args.transaction.id
        : args.transaction,
    }

    return processPayment.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::processPayment
* @see app/Http/Controllers/TransactionController.php:326
* @route '/transactions/{transaction}/payment'
*/
processPayment.post = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: processPayment.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::processPayment
* @see app/Http/Controllers/TransactionController.php:326
* @route '/transactions/{transaction}/payment'
*/
const processPaymentForm = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: processPayment.url(args, options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::processPayment
* @see app/Http/Controllers/TransactionController.php:326
* @route '/transactions/{transaction}/payment'
*/
processPaymentForm.post = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: processPayment.url(args, options),
    method: 'post',
})

processPayment.form = processPaymentForm

/**
* @see \App\Http\Controllers\TransactionController::downloadPdf
* @see app/Http/Controllers/TransactionController.php:508
* @route '/transactions/{transaction}/pdf'
*/
export const downloadPdf = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadPdf.url(args, options),
    method: 'get',
})

downloadPdf.definition = {
    methods: ["get","head"],
    url: '/transactions/{transaction}/pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransactionController::downloadPdf
* @see app/Http/Controllers/TransactionController.php:508
* @route '/transactions/{transaction}/pdf'
*/
downloadPdf.url = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { transaction: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            transaction: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transaction: typeof args.transaction === 'object'
        ? args.transaction.id
        : args.transaction,
    }

    return downloadPdf.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::downloadPdf
* @see app/Http/Controllers/TransactionController.php:508
* @route '/transactions/{transaction}/pdf'
*/
downloadPdf.get = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadPdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::downloadPdf
* @see app/Http/Controllers/TransactionController.php:508
* @route '/transactions/{transaction}/pdf'
*/
downloadPdf.head = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadPdf.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransactionController::downloadPdf
* @see app/Http/Controllers/TransactionController.php:508
* @route '/transactions/{transaction}/pdf'
*/
const downloadPdfForm = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadPdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::downloadPdf
* @see app/Http/Controllers/TransactionController.php:508
* @route '/transactions/{transaction}/pdf'
*/
downloadPdfForm.get = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadPdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::downloadPdf
* @see app/Http/Controllers/TransactionController.php:508
* @route '/transactions/{transaction}/pdf'
*/
downloadPdfForm.head = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadPdf.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

downloadPdf.form = downloadPdfForm

/**
* @see \App\Http\Controllers\TransactionController::printThermal
* @see app/Http/Controllers/TransactionController.php:526
* @route '/transactions/{transaction}/thermal'
*/
export const printThermal = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: printThermal.url(args, options),
    method: 'get',
})

printThermal.definition = {
    methods: ["get","head"],
    url: '/transactions/{transaction}/thermal',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransactionController::printThermal
* @see app/Http/Controllers/TransactionController.php:526
* @route '/transactions/{transaction}/thermal'
*/
printThermal.url = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { transaction: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            transaction: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transaction: typeof args.transaction === 'object'
        ? args.transaction.id
        : args.transaction,
    }

    return printThermal.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::printThermal
* @see app/Http/Controllers/TransactionController.php:526
* @route '/transactions/{transaction}/thermal'
*/
printThermal.get = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: printThermal.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::printThermal
* @see app/Http/Controllers/TransactionController.php:526
* @route '/transactions/{transaction}/thermal'
*/
printThermal.head = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: printThermal.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransactionController::printThermal
* @see app/Http/Controllers/TransactionController.php:526
* @route '/transactions/{transaction}/thermal'
*/
const printThermalForm = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: printThermal.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::printThermal
* @see app/Http/Controllers/TransactionController.php:526
* @route '/transactions/{transaction}/thermal'
*/
printThermalForm.get = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: printThermal.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::printThermal
* @see app/Http/Controllers/TransactionController.php:526
* @route '/transactions/{transaction}/thermal'
*/
printThermalForm.head = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: printThermal.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

printThermal.form = printThermalForm

/**
* @see \App\Http\Controllers\TransactionController::orders
* @see app/Http/Controllers/TransactionController.php:412
* @route '/orders'
*/
export const orders = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orders.url(options),
    method: 'get',
})

orders.definition = {
    methods: ["get","head"],
    url: '/orders',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransactionController::orders
* @see app/Http/Controllers/TransactionController.php:412
* @route '/orders'
*/
orders.url = (options?: RouteQueryOptions) => {
    return orders.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::orders
* @see app/Http/Controllers/TransactionController.php:412
* @route '/orders'
*/
orders.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: orders.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::orders
* @see app/Http/Controllers/TransactionController.php:412
* @route '/orders'
*/
orders.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: orders.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransactionController::orders
* @see app/Http/Controllers/TransactionController.php:412
* @route '/orders'
*/
const ordersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orders.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::orders
* @see app/Http/Controllers/TransactionController.php:412
* @route '/orders'
*/
ordersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orders.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::orders
* @see app/Http/Controllers/TransactionController.php:412
* @route '/orders'
*/
ordersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: orders.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

orders.form = ordersForm

/**
* @see \App\Http\Controllers\TransactionController::bulkUpdateStatus
* @see app/Http/Controllers/TransactionController.php:462
* @route '/orders/bulk-status'
*/
export const bulkUpdateStatus = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: bulkUpdateStatus.url(options),
    method: 'patch',
})

bulkUpdateStatus.definition = {
    methods: ["patch"],
    url: '/orders/bulk-status',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TransactionController::bulkUpdateStatus
* @see app/Http/Controllers/TransactionController.php:462
* @route '/orders/bulk-status'
*/
bulkUpdateStatus.url = (options?: RouteQueryOptions) => {
    return bulkUpdateStatus.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::bulkUpdateStatus
* @see app/Http/Controllers/TransactionController.php:462
* @route '/orders/bulk-status'
*/
bulkUpdateStatus.patch = (options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: bulkUpdateStatus.url(options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\TransactionController::bulkUpdateStatus
* @see app/Http/Controllers/TransactionController.php:462
* @route '/orders/bulk-status'
*/
const bulkUpdateStatusForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkUpdateStatus.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::bulkUpdateStatus
* @see app/Http/Controllers/TransactionController.php:462
* @route '/orders/bulk-status'
*/
bulkUpdateStatusForm.patch = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkUpdateStatus.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

bulkUpdateStatus.form = bulkUpdateStatusForm

/**
* @see \App\Http\Controllers\TransactionController::bulkDestroy
* @see app/Http/Controllers/TransactionController.php:479
* @route '/transactions/bulk-destroy'
*/
export const bulkDestroy = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: bulkDestroy.url(options),
    method: 'delete',
})

bulkDestroy.definition = {
    methods: ["delete"],
    url: '/transactions/bulk-destroy',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TransactionController::bulkDestroy
* @see app/Http/Controllers/TransactionController.php:479
* @route '/transactions/bulk-destroy'
*/
bulkDestroy.url = (options?: RouteQueryOptions) => {
    return bulkDestroy.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::bulkDestroy
* @see app/Http/Controllers/TransactionController.php:479
* @route '/transactions/bulk-destroy'
*/
bulkDestroy.delete = (options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: bulkDestroy.url(options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\TransactionController::bulkDestroy
* @see app/Http/Controllers/TransactionController.php:479
* @route '/transactions/bulk-destroy'
*/
const bulkDestroyForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkDestroy.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::bulkDestroy
* @see app/Http/Controllers/TransactionController.php:479
* @route '/transactions/bulk-destroy'
*/
bulkDestroyForm.delete = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: bulkDestroy.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

bulkDestroy.form = bulkDestroyForm

/**
* @see \App\Http\Controllers\TransactionController::destroy
* @see app/Http/Controllers/TransactionController.php:498
* @route '/transactions/{transaction}'
*/
export const destroy = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/transactions/{transaction}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TransactionController::destroy
* @see app/Http/Controllers/TransactionController.php:498
* @route '/transactions/{transaction}'
*/
destroy.url = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { transaction: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { transaction: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            transaction: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        transaction: typeof args.transaction === 'object'
        ? args.transaction.id
        : args.transaction,
    }

    return destroy.definition.url
            .replace('{transaction}', parsedArgs.transaction.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::destroy
* @see app/Http/Controllers/TransactionController.php:498
* @route '/transactions/{transaction}'
*/
destroy.delete = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

/**
* @see \App\Http\Controllers\TransactionController::destroy
* @see app/Http/Controllers/TransactionController.php:498
* @route '/transactions/{transaction}'
*/
const destroyForm = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TransactionController::destroy
* @see app/Http/Controllers/TransactionController.php:498
* @route '/transactions/{transaction}'
*/
destroyForm.delete = (args: { transaction: number | { id: number } } | [transaction: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'DELETE',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

/**
* @see \App\Http\Controllers\TransactionController::publicInvoice
* @see app/Http/Controllers/TransactionController.php:584
* @route '/invoice/{uuid}'
*/
export const publicInvoice = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicInvoice.url(args, options),
    method: 'get',
})

publicInvoice.definition = {
    methods: ["get","head"],
    url: '/invoice/{uuid}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransactionController::publicInvoice
* @see app/Http/Controllers/TransactionController.php:584
* @route '/invoice/{uuid}'
*/
publicInvoice.url = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return publicInvoice.definition.url
            .replace('{uuid}', parsedArgs.uuid.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::publicInvoice
* @see app/Http/Controllers/TransactionController.php:584
* @route '/invoice/{uuid}'
*/
publicInvoice.get = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicInvoice.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::publicInvoice
* @see app/Http/Controllers/TransactionController.php:584
* @route '/invoice/{uuid}'
*/
publicInvoice.head = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: publicInvoice.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransactionController::publicInvoice
* @see app/Http/Controllers/TransactionController.php:584
* @route '/invoice/{uuid}'
*/
const publicInvoiceForm = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: publicInvoice.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::publicInvoice
* @see app/Http/Controllers/TransactionController.php:584
* @route '/invoice/{uuid}'
*/
publicInvoiceForm.get = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: publicInvoice.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::publicInvoice
* @see app/Http/Controllers/TransactionController.php:584
* @route '/invoice/{uuid}'
*/
publicInvoiceForm.head = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: publicInvoice.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

publicInvoice.form = publicInvoiceForm

/**
* @see \App\Http\Controllers\TransactionController::publicPdf
* @see app/Http/Controllers/TransactionController.php:596
* @route '/invoice/{uuid}/pdf'
*/
export const publicPdf = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicPdf.url(args, options),
    method: 'get',
})

publicPdf.definition = {
    methods: ["get","head"],
    url: '/invoice/{uuid}/pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TransactionController::publicPdf
* @see app/Http/Controllers/TransactionController.php:596
* @route '/invoice/{uuid}/pdf'
*/
publicPdf.url = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return publicPdf.definition.url
            .replace('{uuid}', parsedArgs.uuid.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TransactionController::publicPdf
* @see app/Http/Controllers/TransactionController.php:596
* @route '/invoice/{uuid}/pdf'
*/
publicPdf.get = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicPdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::publicPdf
* @see app/Http/Controllers/TransactionController.php:596
* @route '/invoice/{uuid}/pdf'
*/
publicPdf.head = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: publicPdf.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TransactionController::publicPdf
* @see app/Http/Controllers/TransactionController.php:596
* @route '/invoice/{uuid}/pdf'
*/
const publicPdfForm = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: publicPdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::publicPdf
* @see app/Http/Controllers/TransactionController.php:596
* @route '/invoice/{uuid}/pdf'
*/
publicPdfForm.get = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: publicPdf.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TransactionController::publicPdf
* @see app/Http/Controllers/TransactionController.php:596
* @route '/invoice/{uuid}/pdf'
*/
publicPdfForm.head = (args: { uuid: string | number } | [uuid: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: publicPdf.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

publicPdf.form = publicPdfForm

const TransactionController = { index, create, store, show, updateStatus, processPayment, downloadPdf, printThermal, orders, bulkUpdateStatus, bulkDestroy, destroy, publicInvoice, publicPdf }

export default TransactionController