import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/editions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::index
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
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
* @see \App\Http\Controllers\EditionController::index2
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
export const index2 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index2.url(options),
    method: 'get',
})

index2.definition = {
    methods: ["get","head"],
    url: '/dashboard/editions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::index2
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
index2.url = (options?: RouteQueryOptions) => {
    return index2.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::index2
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
index2.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::index2
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
index2.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index2.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::index2
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
const index2Form = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::index2
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
index2Form.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index2.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::index2
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
index2Form.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index2.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index2.form = index2Form

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/dashboard/edition/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::create
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
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
* @see app/Http/Controllers/EditionController.php:34
* @route '/dashboard/edition/store'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/dashboard/edition/store',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EditionController::store
* @see app/Http/Controllers/EditionController.php:34
* @route '/dashboard/edition/store'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::store
* @see app/Http/Controllers/EditionController.php:34
* @route '/dashboard/edition/store'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EditionController::store
* @see app/Http/Controllers/EditionController.php:34
* @route '/dashboard/edition/store'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EditionController::store
* @see app/Http/Controllers/EditionController.php:34
* @route '/dashboard/edition/store'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
export const show = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/dashboard/edition/{edition}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
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
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
show.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
show.head = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
const showForm = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
showForm.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::show
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
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
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
export const edit = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/dashboard/edition/{edition}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
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
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
edit.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
edit.head = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
const editForm = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
editForm.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::edit
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
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

/**
* @see \App\Http\Controllers\EditionController::update
* @see app/Http/Controllers/EditionController.php:75
* @route '/dashboard/edition/{edition}/update'
*/
export const update = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/dashboard/edition/{edition}/update',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\EditionController::update
* @see app/Http/Controllers/EditionController.php:75
* @route '/dashboard/edition/{edition}/update'
*/
update.url = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return update.definition.url
            .replace('{edition}', parsedArgs.edition.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::update
* @see app/Http/Controllers/EditionController.php:75
* @route '/dashboard/edition/{edition}/update'
*/
update.put = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\EditionController::update
* @see app/Http/Controllers/EditionController.php:75
* @route '/dashboard/edition/{edition}/update'
*/
const updateForm = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EditionController::update
* @see app/Http/Controllers/EditionController.php:75
* @route '/dashboard/edition/{edition}/update'
*/
updateForm.put = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

const EditionController = { index, index2, create, store, show, edit, update }

export default EditionController