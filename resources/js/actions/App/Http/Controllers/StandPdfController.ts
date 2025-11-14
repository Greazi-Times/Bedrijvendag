import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\StandPdfController::downloadAll
* @see app/Http/Controllers/StandPdfController.php:13
* @route '/dashboard/stands/pdf'
*/
export const downloadAll = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadAll.url(options),
    method: 'get',
})

downloadAll.definition = {
    methods: ["get","head"],
    url: '/dashboard/stands/pdf',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\StandPdfController::downloadAll
* @see app/Http/Controllers/StandPdfController.php:13
* @route '/dashboard/stands/pdf'
*/
downloadAll.url = (options?: RouteQueryOptions) => {
    return downloadAll.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\StandPdfController::downloadAll
* @see app/Http/Controllers/StandPdfController.php:13
* @route '/dashboard/stands/pdf'
*/
downloadAll.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: downloadAll.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StandPdfController::downloadAll
* @see app/Http/Controllers/StandPdfController.php:13
* @route '/dashboard/stands/pdf'
*/
downloadAll.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: downloadAll.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\StandPdfController::downloadAll
* @see app/Http/Controllers/StandPdfController.php:13
* @route '/dashboard/stands/pdf'
*/
const downloadAllForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadAll.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StandPdfController::downloadAll
* @see app/Http/Controllers/StandPdfController.php:13
* @route '/dashboard/stands/pdf'
*/
downloadAllForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadAll.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\StandPdfController::downloadAll
* @see app/Http/Controllers/StandPdfController.php:13
* @route '/dashboard/stands/pdf'
*/
downloadAllForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: downloadAll.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

downloadAll.form = downloadAllForm

const StandPdfController = { downloadAll }

export default StandPdfController