import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\BorrelController::store
* @see app/Http/Controllers/BorrelController.php:10
* @route '/borrel-enrollment'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/borrel-enrollment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BorrelController::store
* @see app/Http/Controllers/BorrelController.php:10
* @route '/borrel-enrollment'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BorrelController::store
* @see app/Http/Controllers/BorrelController.php:10
* @route '/borrel-enrollment'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BorrelController::store
* @see app/Http/Controllers/BorrelController.php:10
* @route '/borrel-enrollment'
*/
const storeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BorrelController::store
* @see app/Http/Controllers/BorrelController.php:10
* @route '/borrel-enrollment'
*/
storeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: store.url(options),
    method: 'post',
})

store.form = storeForm

const BorrelController = { store }

export default BorrelController