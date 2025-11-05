import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\CompanyController::publicIndex
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
export const publicIndex = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicIndex.url(options),
    method: 'get',
})

publicIndex.definition = {
    methods: ["get","head"],
    url: '/bedrijven',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CompanyController::publicIndex
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
publicIndex.url = (options?: RouteQueryOptions) => {
    return publicIndex.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::publicIndex
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
publicIndex.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: publicIndex.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::publicIndex
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
publicIndex.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: publicIndex.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CompanyController::publicIndex
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
const publicIndexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: publicIndex.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::publicIndex
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
publicIndexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: publicIndex.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::publicIndex
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
publicIndexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: publicIndex.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

publicIndex.form = publicIndexForm

/**
* @see \App\Http\Controllers\CompanyController::create
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
export const create = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

create.definition = {
    methods: ["get","head"],
    url: '/dashboard/company/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CompanyController::create
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
create.url = (options?: RouteQueryOptions) => {
    return create.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::create
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
create.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::create
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
create.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: create.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CompanyController::create
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
const createForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::create
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
createForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: create.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::create
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
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
* @see \App\Http\Controllers\CompanyController::store
* @see app/Http/Controllers/CompanyController.php:60
* @route '/dashboard/company/store'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/dashboard/company/store',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CompanyController::store
* @see app/Http/Controllers/CompanyController.php:60
* @route '/dashboard/company/store'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::store
* @see app/Http/Controllers/CompanyController.php:60
* @route '/dashboard/company/store'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CompanyController::store
* @see app/Http/Controllers/CompanyController.php:60
* @route '/dashboard/company/store'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CompanyController::store
* @see app/Http/Controllers/CompanyController.php:60
* @route '/dashboard/company/store'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

/**
* @see \App\Http\Controllers\CompanyController::show
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
export const show = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/dashboard/company/{company}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CompanyController::show
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
show.url = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { company: args }
    }

    if (Array.isArray(args)) {
        args = {
            company: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        company: args.company,
    }

    return show.definition.url
            .replace('{company}', parsedArgs.company.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::show
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
show.get = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::show
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
show.head = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CompanyController::show
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
const showForm = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::show
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
showForm.get = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::show
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
showForm.head = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\CompanyController::edit
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
export const edit = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

edit.definition = {
    methods: ["get","head"],
    url: '/dashboard/company/{company}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CompanyController::edit
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
edit.url = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { company: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { company: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            company: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        company: typeof args.company === 'object'
        ? args.company.id
        : args.company,
    }

    return edit.definition.url
            .replace('{company}', parsedArgs.company.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::edit
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
edit.get = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::edit
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
edit.head = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: edit.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CompanyController::edit
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
const editForm = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::edit
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
editForm.get = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: edit.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::edit
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
editForm.head = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
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
* @see \App\Http\Controllers\CompanyController::update
* @see app/Http/Controllers/CompanyController.php:132
* @route '/dashboard/company/{company}/update'
*/
export const update = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

update.definition = {
    methods: ["put"],
    url: '/dashboard/company/{company}/update',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\CompanyController::update
* @see app/Http/Controllers/CompanyController.php:132
* @route '/dashboard/company/{company}/update'
*/
update.url = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { company: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { company: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            company: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        company: typeof args.company === 'object'
        ? args.company.id
        : args.company,
    }

    return update.definition.url
            .replace('{company}', parsedArgs.company.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::update
* @see app/Http/Controllers/CompanyController.php:132
* @route '/dashboard/company/{company}/update'
*/
update.put = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: update.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\CompanyController::update
* @see app/Http/Controllers/CompanyController.php:132
* @route '/dashboard/company/{company}/update'
*/
const updateForm = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CompanyController::update
* @see app/Http/Controllers/CompanyController.php:132
* @route '/dashboard/company/{company}/update'
*/
updateForm.put = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: update.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

update.form = updateForm

/**
* @see \App\Http\Controllers\CompanyController::destroy
* @see app/Http/Controllers/CompanyController.php:172
* @route '/dashboard/company/{company}/remove'
*/
export const destroy = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: destroy.url(args, options),
    method: 'put',
})

destroy.definition = {
    methods: ["put"],
    url: '/dashboard/company/{company}/remove',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\CompanyController::destroy
* @see app/Http/Controllers/CompanyController.php:172
* @route '/dashboard/company/{company}/remove'
*/
destroy.url = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { company: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { company: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            company: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        company: typeof args.company === 'object'
        ? args.company.id
        : args.company,
    }

    return destroy.definition.url
            .replace('{company}', parsedArgs.company.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::destroy
* @see app/Http/Controllers/CompanyController.php:172
* @route '/dashboard/company/{company}/remove'
*/
destroy.put = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: destroy.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\CompanyController::destroy
* @see app/Http/Controllers/CompanyController.php:172
* @route '/dashboard/company/{company}/remove'
*/
const destroyForm = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CompanyController::destroy
* @see app/Http/Controllers/CompanyController.php:172
* @route '/dashboard/company/{company}/remove'
*/
destroyForm.put = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: destroy.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

destroy.form = destroyForm

const CompanyController = { publicIndex, create, store, show, edit, update, destroy }

export default CompanyController