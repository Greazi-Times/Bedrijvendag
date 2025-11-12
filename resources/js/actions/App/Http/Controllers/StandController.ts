import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StandController::index
* @see app/Http/Controllers/StandController.php:12
* @route '/dashboard/stands'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/dashboard/stands',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StandController::index
* @see app/Http/Controllers/StandController.php:12
* @route '/dashboard/stands'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StandController::index
* @see app/Http/Controllers/StandController.php:12
* @route '/dashboard/stands'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StandController::index
* @see app/Http/Controllers/StandController.php:12
* @route '/dashboard/stands'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StandController::index
* @see app/Http/Controllers/StandController.php:12
* @route '/dashboard/stands'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StandController::index
* @see app/Http/Controllers/StandController.php:12
* @route '/dashboard/stands'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StandController::index
* @see app/Http/Controllers/StandController.php:12
* @route '/dashboard/stands'
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
* @see \App\Http\Controllers\StandController::update
* @see app/Http/Controllers/StandController.php:61
* @route '/dashboard/stands/{stand}'
*/
export const update = (args: { stand: number | { id: number } } | [stand: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/dashboard/stands/{stand}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\StandController::update
* @see app/Http/Controllers/StandController.php:61
* @route '/dashboard/stands/{stand}'
*/
update.url = (args: { stand: number | { id: number } } | [stand: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { stand: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { stand: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            stand: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        stand: typeof args.stand === 'object'
        ? args.stand.id
        : args.stand,
    }

    return update.definition.url
            .replace('{stand}', parsedArgs.stand.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\StandController::update
* @see app/Http/Controllers/StandController.php:61
* @route '/dashboard/stands/{stand}'
*/
update.patch = (args: { stand: number | { id: number } } | [stand: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\StandController::update
* @see app/Http/Controllers/StandController.php:61
* @route '/dashboard/stands/{stand}'
*/
const updateForm = (args: { stand: number | { id: number } } | [stand: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\StandController::update
* @see app/Http/Controllers/StandController.php:61
* @route '/dashboard/stands/{stand}'
*/
updateForm.patch = (args: { stand: number | { id: number } } | [stand: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PATCH',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\StandController::view
* @see app/Http/Controllers/StandController.php:39
* @route '/plattegrond'
*/
export const view = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(options),
    method: 'get',
})

view.definition = {
    methods: ["get","head"],
    url: '/plattegrond',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StandController::view
* @see app/Http/Controllers/StandController.php:39
* @route '/plattegrond'
*/
view.url = (options?: RouteQueryOptions) => {
    return view.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StandController::view
* @see app/Http/Controllers/StandController.php:39
* @route '/plattegrond'
*/
view.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: view.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StandController::view
* @see app/Http/Controllers/StandController.php:39
* @route '/plattegrond'
*/
view.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: view.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StandController::view
* @see app/Http/Controllers/StandController.php:39
* @route '/plattegrond'
*/
const viewForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: view.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StandController::view
* @see app/Http/Controllers/StandController.php:39
* @route '/plattegrond'
*/
viewForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: view.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StandController::view
* @see app/Http/Controllers/StandController.php:39
* @route '/plattegrond'
*/
viewForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: view.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

view.form = viewForm

const StandController = { index, update, view }

export default StandController