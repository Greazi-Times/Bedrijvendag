import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../wayfinder'
/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:20
* @route '/login'
*/
export const login = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})

login.definition = {
    methods: ["get","head"],
    url: '/login',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:20
* @route '/login'
*/
login.url = (options?: RouteQueryOptions) => {
    return login.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:20
* @route '/login'
*/
login.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: login.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:20
* @route '/login'
*/
login.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: login.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:20
* @route '/login'
*/
const loginForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: login.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:20
* @route '/login'
*/
loginForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: login.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::login
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:20
* @route '/login'
*/
loginForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: login.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

login.form = loginForm

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:54
* @route '/logout'
*/
export const logout = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

logout.definition = {
    methods: ["post"],
    url: '/logout',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:54
* @route '/logout'
*/
logout.url = (options?: RouteQueryOptions) => {
    return logout.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:54
* @route '/logout'
*/
logout.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:54
* @route '/logout'
*/
const logoutForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Auth\AuthenticatedSessionController::logout
* @see app/Http/Controllers/Auth/AuthenticatedSessionController.php:54
* @route '/logout'
*/
logoutForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: logout.url(options),
    method: 'post',
})

logout.form = logoutForm

/**
* @see routes/web.php:6
* @route '/'
*/
export const home = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

home.definition = {
    methods: ["get","head"],
    url: '/',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:6
* @route '/'
*/
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see routes/web.php:6
* @route '/'
*/
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

/**
* @see routes/web.php:6
* @route '/'
*/
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see routes/web.php:6
* @route '/'
*/
const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see routes/web.php:6
* @route '/'
*/
homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see routes/web.php:6
* @route '/'
*/
homeForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

home.form = homeForm

/**
* @see routes/web.php:10
* @route '/editions'
*/
export const editions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editions.url(options),
    method: 'get',
})

editions.definition = {
    methods: ["get","head"],
    url: '/editions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:10
* @route '/editions'
*/
editions.url = (options?: RouteQueryOptions) => {
    return editions.definition.url + queryParams(options)
}

/**
* @see routes/web.php:10
* @route '/editions'
*/
editions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editions.url(options),
    method: 'get',
})

/**
* @see routes/web.php:10
* @route '/editions'
*/
editions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editions.url(options),
    method: 'head',
})

/**
* @see routes/web.php:10
* @route '/editions'
*/
const editionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: editions.url(options),
    method: 'get',
})

/**
* @see routes/web.php:10
* @route '/editions'
*/
editionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: editions.url(options),
    method: 'get',
})

/**
* @see routes/web.php:10
* @route '/editions'
*/
editionsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: editions.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

editions.form = editionsForm

/**
* @see routes/web.php:14
* @route '/bedrijven'
*/
export const companies = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: companies.url(options),
    method: 'get',
})

companies.definition = {
    methods: ["get","head"],
    url: '/bedrijven',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:14
* @route '/bedrijven'
*/
companies.url = (options?: RouteQueryOptions) => {
    return companies.definition.url + queryParams(options)
}

/**
* @see routes/web.php:14
* @route '/bedrijven'
*/
companies.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: companies.url(options),
    method: 'get',
})

/**
* @see routes/web.php:14
* @route '/bedrijven'
*/
companies.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: companies.url(options),
    method: 'head',
})

/**
* @see routes/web.php:14
* @route '/bedrijven'
*/
const companiesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: companies.url(options),
    method: 'get',
})

/**
* @see routes/web.php:14
* @route '/bedrijven'
*/
companiesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: companies.url(options),
    method: 'get',
})

/**
* @see routes/web.php:14
* @route '/bedrijven'
*/
companiesForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: companies.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

companies.form = companiesForm

/**
* @see routes/web.php:18
* @route '/partners'
*/
export const partners = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: partners.url(options),
    method: 'get',
})

partners.definition = {
    methods: ["get","head"],
    url: '/partners',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:18
* @route '/partners'
*/
partners.url = (options?: RouteQueryOptions) => {
    return partners.definition.url + queryParams(options)
}

/**
* @see routes/web.php:18
* @route '/partners'
*/
partners.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: partners.url(options),
    method: 'get',
})

/**
* @see routes/web.php:18
* @route '/partners'
*/
partners.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: partners.url(options),
    method: 'head',
})

/**
* @see routes/web.php:18
* @route '/partners'
*/
const partnersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: partners.url(options),
    method: 'get',
})

/**
* @see routes/web.php:18
* @route '/partners'
*/
partnersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: partners.url(options),
    method: 'get',
})

/**
* @see routes/web.php:18
* @route '/partners'
*/
partnersForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: partners.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

partners.form = partnersForm

/**
* @see routes/web.php:22
* @route '/over-ons'
*/
export const aboutUs = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: aboutUs.url(options),
    method: 'get',
})

aboutUs.definition = {
    methods: ["get","head"],
    url: '/over-ons',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:22
* @route '/over-ons'
*/
aboutUs.url = (options?: RouteQueryOptions) => {
    return aboutUs.definition.url + queryParams(options)
}

/**
* @see routes/web.php:22
* @route '/over-ons'
*/
aboutUs.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: aboutUs.url(options),
    method: 'get',
})

/**
* @see routes/web.php:22
* @route '/over-ons'
*/
aboutUs.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: aboutUs.url(options),
    method: 'head',
})

/**
* @see routes/web.php:22
* @route '/over-ons'
*/
const aboutUsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: aboutUs.url(options),
    method: 'get',
})

/**
* @see routes/web.php:22
* @route '/over-ons'
*/
aboutUsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: aboutUs.url(options),
    method: 'get',
})

/**
* @see routes/web.php:22
* @route '/over-ons'
*/
aboutUsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: aboutUs.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

aboutUs.form = aboutUsForm

/**
* @see routes/web.php:26
* @route '/contact'
*/
export const contact = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: contact.url(options),
    method: 'get',
})

contact.definition = {
    methods: ["get","head"],
    url: '/contact',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:26
* @route '/contact'
*/
contact.url = (options?: RouteQueryOptions) => {
    return contact.definition.url + queryParams(options)
}

/**
* @see routes/web.php:26
* @route '/contact'
*/
contact.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: contact.url(options),
    method: 'get',
})

/**
* @see routes/web.php:26
* @route '/contact'
*/
contact.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: contact.url(options),
    method: 'head',
})

/**
* @see routes/web.php:26
* @route '/contact'
*/
const contactForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: contact.url(options),
    method: 'get',
})

/**
* @see routes/web.php:26
* @route '/contact'
*/
contactForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: contact.url(options),
    method: 'get',
})

/**
* @see routes/web.php:26
* @route '/contact'
*/
contactForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: contact.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

contact.form = contactForm

/**
* @see routes/web.php:30
* @route '/dashboard'
*/
export const dashboard = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

dashboard.definition = {
    methods: ["get","head"],
    url: '/dashboard',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:30
* @route '/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see routes/web.php:30
* @route '/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see routes/web.php:30
* @route '/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see routes/web.php:30
* @route '/dashboard'
*/
const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see routes/web.php:30
* @route '/dashboard'
*/
dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see routes/web.php:30
* @route '/dashboard'
*/
dashboardForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

dashboard.form = dashboardForm

/**
* @see routes/web.php:35
* @route '/avans'
*/
export const avans = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: avans.url(options),
    method: 'get',
})

avans.definition = {
    methods: ["get","head"],
    url: '/avans',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:35
* @route '/avans'
*/
avans.url = (options?: RouteQueryOptions) => {
    return avans.definition.url + queryParams(options)
}

/**
* @see routes/web.php:35
* @route '/avans'
*/
avans.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: avans.url(options),
    method: 'get',
})

/**
* @see routes/web.php:35
* @route '/avans'
*/
avans.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: avans.url(options),
    method: 'head',
})

/**
* @see routes/web.php:35
* @route '/avans'
*/
const avansForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: avans.url(options),
    method: 'get',
})

/**
* @see routes/web.php:35
* @route '/avans'
*/
avansForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: avans.url(options),
    method: 'get',
})

/**
* @see routes/web.php:35
* @route '/avans'
*/
avansForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: avans.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

avans.form = avansForm

/**
* @see routes/web.php:40
* @route '/privacy-policy'
*/
export const privacyPolicy = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: privacyPolicy.url(options),
    method: 'get',
})

privacyPolicy.definition = {
    methods: ["get","head"],
    url: '/privacy-policy',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:40
* @route '/privacy-policy'
*/
privacyPolicy.url = (options?: RouteQueryOptions) => {
    return privacyPolicy.definition.url + queryParams(options)
}

/**
* @see routes/web.php:40
* @route '/privacy-policy'
*/
privacyPolicy.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: privacyPolicy.url(options),
    method: 'get',
})

/**
* @see routes/web.php:40
* @route '/privacy-policy'
*/
privacyPolicy.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: privacyPolicy.url(options),
    method: 'head',
})

/**
* @see routes/web.php:40
* @route '/privacy-policy'
*/
const privacyPolicyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: privacyPolicy.url(options),
    method: 'get',
})

/**
* @see routes/web.php:40
* @route '/privacy-policy'
*/
privacyPolicyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: privacyPolicy.url(options),
    method: 'get',
})

/**
* @see routes/web.php:40
* @route '/privacy-policy'
*/
privacyPolicyForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: privacyPolicy.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

privacyPolicy.form = privacyPolicyForm

/**
* @see routes/web.php:44
* @route '/terms-of-service'
*/
export const termsOfService = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: termsOfService.url(options),
    method: 'get',
})

termsOfService.definition = {
    methods: ["get","head"],
    url: '/terms-of-service',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:44
* @route '/terms-of-service'
*/
termsOfService.url = (options?: RouteQueryOptions) => {
    return termsOfService.definition.url + queryParams(options)
}

/**
* @see routes/web.php:44
* @route '/terms-of-service'
*/
termsOfService.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: termsOfService.url(options),
    method: 'get',
})

/**
* @see routes/web.php:44
* @route '/terms-of-service'
*/
termsOfService.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: termsOfService.url(options),
    method: 'head',
})

/**
* @see routes/web.php:44
* @route '/terms-of-service'
*/
const termsOfServiceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: termsOfService.url(options),
    method: 'get',
})

/**
* @see routes/web.php:44
* @route '/terms-of-service'
*/
termsOfServiceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: termsOfService.url(options),
    method: 'get',
})

/**
* @see routes/web.php:44
* @route '/terms-of-service'
*/
termsOfServiceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: termsOfService.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

termsOfService.form = termsOfServiceForm

/**
* @see routes/web.php:48
* @route '/cookie-policy'
*/
export const cookiePolicy = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cookiePolicy.url(options),
    method: 'get',
})

cookiePolicy.definition = {
    methods: ["get","head"],
    url: '/cookie-policy',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/web.php:48
* @route '/cookie-policy'
*/
cookiePolicy.url = (options?: RouteQueryOptions) => {
    return cookiePolicy.definition.url + queryParams(options)
}

/**
* @see routes/web.php:48
* @route '/cookie-policy'
*/
cookiePolicy.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cookiePolicy.url(options),
    method: 'get',
})

/**
* @see routes/web.php:48
* @route '/cookie-policy'
*/
cookiePolicy.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cookiePolicy.url(options),
    method: 'head',
})

/**
* @see routes/web.php:48
* @route '/cookie-policy'
*/
const cookiePolicyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cookiePolicy.url(options),
    method: 'get',
})

/**
* @see routes/web.php:48
* @route '/cookie-policy'
*/
cookiePolicyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cookiePolicy.url(options),
    method: 'get',
})

/**
* @see routes/web.php:48
* @route '/cookie-policy'
*/
cookiePolicyForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cookiePolicy.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

cookiePolicy.form = cookiePolicyForm

/**
* @see routes/settings.php:22
* @route '/settings/appearance'
*/
export const appearance = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: appearance.url(options),
    method: 'get',
})

appearance.definition = {
    methods: ["get","head"],
    url: '/settings/appearance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see routes/settings.php:22
* @route '/settings/appearance'
*/
appearance.url = (options?: RouteQueryOptions) => {
    return appearance.definition.url + queryParams(options)
}

/**
* @see routes/settings.php:22
* @route '/settings/appearance'
*/
appearance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: appearance.url(options),
    method: 'get',
})

/**
* @see routes/settings.php:22
* @route '/settings/appearance'
*/
appearance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: appearance.url(options),
    method: 'head',
})

/**
* @see routes/settings.php:22
* @route '/settings/appearance'
*/
const appearanceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: appearance.url(options),
    method: 'get',
})

/**
* @see routes/settings.php:22
* @route '/settings/appearance'
*/
appearanceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: appearance.url(options),
    method: 'get',
})

/**
* @see routes/settings.php:22
* @route '/settings/appearance'
*/
appearanceForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: appearance.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

appearance.form = appearanceForm

/**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
* @see app/Http/Controllers/Auth/RegisteredUserController.php:21
* @route '/register'
*/
export const register = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: register.url(options),
    method: 'get',
})

register.definition = {
    methods: ["get","head"],
    url: '/register',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
* @see app/Http/Controllers/Auth/RegisteredUserController.php:21
* @route '/register'
*/
register.url = (options?: RouteQueryOptions) => {
    return register.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
* @see app/Http/Controllers/Auth/RegisteredUserController.php:21
* @route '/register'
*/
register.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: register.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
* @see app/Http/Controllers/Auth/RegisteredUserController.php:21
* @route '/register'
*/
register.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: register.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
* @see app/Http/Controllers/Auth/RegisteredUserController.php:21
* @route '/register'
*/
const registerForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: register.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
* @see app/Http/Controllers/Auth/RegisteredUserController.php:21
* @route '/register'
*/
registerForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: register.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Auth\RegisteredUserController::register
* @see app/Http/Controllers/Auth/RegisteredUserController.php:21
* @route '/register'
*/
registerForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: register.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

register.form = registerForm
