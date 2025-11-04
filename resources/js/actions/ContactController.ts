import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../wayfinder'
/**
* @see \ContactController::send
* @see [unknown]:0
* @route '/contact/send'
*/
export const send = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: send.url(options),
    method: 'post',
})

send.definition = {
    methods: ["post"],
    url: '/contact/send',
} satisfies RouteDefinition<["post"]>

/**
* @see \ContactController::send
* @see [unknown]:0
* @route '/contact/send'
*/
send.url = (options?: RouteQueryOptions) => {
    return send.definition.url + queryParams(options)
}

/**
* @see \ContactController::send
* @see [unknown]:0
* @route '/contact/send'
*/
send.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: send.url(options),
    method: 'post',
})

/**
* @see \ContactController::send
* @see [unknown]:0
* @route '/contact/send'
*/
const sendForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: send.url(options),
    method: 'post',
})

/**
* @see \ContactController::send
* @see [unknown]:0
* @route '/contact/send'
*/
sendForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: send.url(options),
    method: 'post',
})

send.form = sendForm

const ContactController = { send }

export default ContactController