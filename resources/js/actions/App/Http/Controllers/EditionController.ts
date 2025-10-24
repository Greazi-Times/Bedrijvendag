import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/dashboard/editions'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/dashboard/editions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/dashboard/editions'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/dashboard/editions'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/dashboard/editions'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/dashboard/editions'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/dashboard/editions'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/dashboard/editions'
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
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/dashboard/editions/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions/create'
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
* @see \App\Http\Controllers\EditionController::store
* @see app/Http/Controllers/EditionController.php:25
* @route '/dashboard/editions/store'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/dashboard/editions/store',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EditionController::store
* @see app/Http/Controllers/EditionController.php:25
* @route '/dashboard/editions/store'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::store
* @see app/Http/Controllers/EditionController.php:25
* @route '/dashboard/editions/store'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EditionController::store
* @see app/Http/Controllers/EditionController.php:25
* @route '/dashboard/editions/store'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EditionController::store
* @see app/Http/Controllers/EditionController.php:25
* @route '/dashboard/editions/store'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:45
* @route '/dashboard/editions/{edition}'
*/
export const show = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/dashboard/editions/{edition}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:45
* @route '/dashboard/editions/{edition}'
*/
show.url = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { edition: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { edition: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            edition: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        edition: typeof args.edition === 'object'
        ? args.edition.id
        : args.edition,
    }

    return show.definition.url
            .replace('{edition}', parsedArgs.edition.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:45
* @route '/dashboard/editions/{edition}'
*/
show.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:45
* @route '/dashboard/editions/{edition}'
*/
show.head = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:45
* @route '/dashboard/editions/{edition}'
*/
const showForm = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:45
* @route '/dashboard/editions/{edition}'
*/
showForm.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:45
* @route '/dashboard/editions/{edition}'
*/
showForm.head = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:52
* @route '/dashboard/editions/{edition}/edit'
*/
export const edit = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/dashboard/editions/{edition}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:52
* @route '/dashboard/editions/{edition}/edit'
*/
edit.url = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { edition: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { edition: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            edition: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        edition: typeof args.edition === 'object'
        ? args.edition.id
        : args.edition,
    }

    return edit.definition.url
            .replace('{edition}', parsedArgs.edition.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:52
* @route '/dashboard/editions/{edition}/edit'
*/
edit.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:52
* @route '/dashboard/editions/{edition}/edit'
*/
edit.head = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:52
* @route '/dashboard/editions/{edition}/edit'
*/
const editForm = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:52
* @route '/dashboard/editions/{edition}/edit'
*/
editForm.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:52
* @route '/dashboard/editions/{edition}/edit'
*/
editForm.head = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

edit.form = editForm

const EditionController = { index, create, store, show, edit }

export default EditionController