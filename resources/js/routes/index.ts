import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition, applyUrlDefaults } from './../wayfinder'
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
* @see \App\Http\Controllers\WelcomeController::home
* @see app/Http/Controllers/WelcomeController.php:11
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
* @see \App\Http\Controllers\WelcomeController::home
* @see app/Http/Controllers/WelcomeController.php:11
* @route '/'
*/
home.url = (options?: RouteQueryOptions) => {
    return home.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WelcomeController::home
* @see app/Http/Controllers/WelcomeController.php:11
* @route '/'
*/
home.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WelcomeController::home
* @see app/Http/Controllers/WelcomeController.php:11
* @route '/'
*/
home.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: home.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\WelcomeController::home
* @see app/Http/Controllers/WelcomeController.php:11
* @route '/'
*/
const homeForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WelcomeController::home
* @see app/Http/Controllers/WelcomeController.php:11
* @route '/'
*/
homeForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: home.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WelcomeController::home
* @see app/Http/Controllers/WelcomeController.php:11
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
* @see \App\Http\Controllers\EditionController::editions
* @see app/Http/Controllers/EditionController.php:11
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
* @see \App\Http\Controllers\EditionController::editions
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
*/
editions.url = (options?: RouteQueryOptions) => {
    return editions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::editions
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
*/
editions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::editions
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
*/
editions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::editions
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
*/
const editionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: editions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::editions
* @see app/Http/Controllers/EditionController.php:11
* @route '/editions'
*/
editionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: editions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::editions
* @see app/Http/Controllers/EditionController.php:11
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
* @see \App\Http\Controllers\CompanyController::companies
* @see app/Http/Controllers/CompanyController.php:28
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
* @see \App\Http\Controllers\CompanyController::companies
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
companies.url = (options?: RouteQueryOptions) => {
    return companies.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::companies
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
companies.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: companies.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::companies
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
companies.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: companies.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CompanyController::companies
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
const companiesForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: companies.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::companies
* @see app/Http/Controllers/CompanyController.php:28
* @route '/bedrijven'
*/
companiesForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: companies.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::companies
* @see app/Http/Controllers/CompanyController.php:28
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
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
* @route '/partners'
*/
partners.url = (options?: RouteQueryOptions) => {
    return partners.definition.url + queryParams(options)
}

/**
* @see [serialized-closure]:2
* @route '/partners'
*/
partners.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: partners.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/partners'
*/
partners.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: partners.url(options),
    method: 'head',
})

/**
* @see [serialized-closure]:2
* @route '/partners'
*/
const partnersForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: partners.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/partners'
*/
partnersForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: partners.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
* @route '/over-ons'
*/
aboutUs.url = (options?: RouteQueryOptions) => {
    return aboutUs.definition.url + queryParams(options)
}

/**
* @see [serialized-closure]:2
* @route '/over-ons'
*/
aboutUs.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: aboutUs.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/over-ons'
*/
aboutUs.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: aboutUs.url(options),
    method: 'head',
})

/**
* @see [serialized-closure]:2
* @route '/over-ons'
*/
const aboutUsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: aboutUs.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/over-ons'
*/
aboutUsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: aboutUs.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
* @route '/contact'
*/
contact.url = (options?: RouteQueryOptions) => {
    return contact.definition.url + queryParams(options)
}

/**
* @see [serialized-closure]:2
* @route '/contact'
*/
contact.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: contact.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/contact'
*/
contact.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: contact.url(options),
    method: 'head',
})

/**
* @see [serialized-closure]:2
* @route '/contact'
*/
const contactForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: contact.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/contact'
*/
contactForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: contact.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
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
* @see \App\Http\Controllers\BorrelController::storeBorrel
* @see app/Http/Controllers/BorrelController.php:10
* @route '/borrel-enrollment'
*/
export const storeBorrel = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBorrel.url(options),
    method: 'post',
})

storeBorrel.definition = {
    methods: ["post"],
    url: '/borrel-enrollment',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\BorrelController::storeBorrel
* @see app/Http/Controllers/BorrelController.php:10
* @route '/borrel-enrollment'
*/
storeBorrel.url = (options?: RouteQueryOptions) => {
    return storeBorrel.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\BorrelController::storeBorrel
* @see app/Http/Controllers/BorrelController.php:10
* @route '/borrel-enrollment'
*/
storeBorrel.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeBorrel.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BorrelController::storeBorrel
* @see app/Http/Controllers/BorrelController.php:10
* @route '/borrel-enrollment'
*/
const storeBorrelForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBorrel.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\BorrelController::storeBorrel
* @see app/Http/Controllers/BorrelController.php:10
* @route '/borrel-enrollment'
*/
storeBorrelForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeBorrel.url(options),
    method: 'post',
})

storeBorrel.form = storeBorrelForm

/**
* @see \App\Http\Controllers\DashboardController::dashboard
* @see app/Http/Controllers/DashboardController.php:12
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
* @see \App\Http\Controllers\DashboardController::dashboard
* @see app/Http/Controllers/DashboardController.php:12
* @route '/dashboard'
*/
dashboard.url = (options?: RouteQueryOptions) => {
    return dashboard.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\DashboardController::dashboard
* @see app/Http/Controllers/DashboardController.php:12
* @route '/dashboard'
*/
dashboard.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::dashboard
* @see app/Http/Controllers/DashboardController.php:12
* @route '/dashboard'
*/
dashboard.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashboard.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\DashboardController::dashboard
* @see app/Http/Controllers/DashboardController.php:12
* @route '/dashboard'
*/
const dashboardForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::dashboard
* @see app/Http/Controllers/DashboardController.php:12
* @route '/dashboard'
*/
dashboardForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashboard.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\DashboardController::dashboard
* @see app/Http/Controllers/DashboardController.php:12
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
* @see \App\Http\Controllers\CompanyController::createCompany
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
export const createCompany = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createCompany.url(options),
    method: 'get',
})

createCompany.definition = {
    methods: ["get","head"],
    url: '/dashboard/company/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CompanyController::createCompany
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
createCompany.url = (options?: RouteQueryOptions) => {
    return createCompany.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::createCompany
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
createCompany.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createCompany.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::createCompany
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
createCompany.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: createCompany.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CompanyController::createCompany
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
const createCompanyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: createCompany.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::createCompany
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
createCompanyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: createCompany.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::createCompany
* @see app/Http/Controllers/CompanyController.php:52
* @route '/dashboard/company/create'
*/
createCompanyForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: createCompany.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

createCompany.form = createCompanyForm

/**
* @see \App\Http\Controllers\CompanyController::storeCompany
* @see app/Http/Controllers/CompanyController.php:60
* @route '/dashboard/company/store'
*/
export const storeCompany = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeCompany.url(options),
    method: 'post',
})

storeCompany.definition = {
    methods: ["post"],
    url: '/dashboard/company/store',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\CompanyController::storeCompany
* @see app/Http/Controllers/CompanyController.php:60
* @route '/dashboard/company/store'
*/
storeCompany.url = (options?: RouteQueryOptions) => {
    return storeCompany.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::storeCompany
* @see app/Http/Controllers/CompanyController.php:60
* @route '/dashboard/company/store'
*/
storeCompany.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeCompany.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CompanyController::storeCompany
* @see app/Http/Controllers/CompanyController.php:60
* @route '/dashboard/company/store'
*/
const storeCompanyForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeCompany.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CompanyController::storeCompany
* @see app/Http/Controllers/CompanyController.php:60
* @route '/dashboard/company/store'
*/
storeCompanyForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeCompany.url(options),
    method: 'post',
})

storeCompany.form = storeCompanyForm

/**
* @see \App\Http\Controllers\CompanyController::showCompany
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
export const showCompany = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showCompany.url(args, options),
    method: 'get',
})

showCompany.definition = {
    methods: ["get","head"],
    url: '/dashboard/company/{company}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CompanyController::showCompany
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
showCompany.url = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions) => {
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

    return showCompany.definition.url
            .replace('{company}', parsedArgs.company.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::showCompany
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
showCompany.get = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showCompany.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::showCompany
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
showCompany.head = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showCompany.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CompanyController::showCompany
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
const showCompanyForm = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showCompany.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::showCompany
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
showCompanyForm.get = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showCompany.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::showCompany
* @see app/Http/Controllers/CompanyController.php:102
* @route '/dashboard/company/{company}'
*/
showCompanyForm.head = (args: { company: string | number } | [company: string | number ] | string | number, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showCompany.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showCompany.form = showCompanyForm

/**
* @see \App\Http\Controllers\CompanyController::editCompany
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
export const editCompany = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editCompany.url(args, options),
    method: 'get',
})

editCompany.definition = {
    methods: ["get","head"],
    url: '/dashboard/company/{company}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\CompanyController::editCompany
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
editCompany.url = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return editCompany.definition.url
            .replace('{company}', parsedArgs.company.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::editCompany
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
editCompany.get = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editCompany.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::editCompany
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
editCompany.head = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editCompany.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\CompanyController::editCompany
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
const editCompanyForm = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: editCompany.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::editCompany
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
editCompanyForm.get = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: editCompany.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\CompanyController::editCompany
* @see app/Http/Controllers/CompanyController.php:112
* @route '/dashboard/company/{company}/edit'
*/
editCompanyForm.head = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: editCompany.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

editCompany.form = editCompanyForm

/**
* @see \App\Http\Controllers\CompanyController::updateCompany
* @see app/Http/Controllers/CompanyController.php:132
* @route '/dashboard/company/{company}/update'
*/
export const updateCompany = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateCompany.url(args, options),
    method: 'put',
})

updateCompany.definition = {
    methods: ["put"],
    url: '/dashboard/company/{company}/update',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\CompanyController::updateCompany
* @see app/Http/Controllers/CompanyController.php:132
* @route '/dashboard/company/{company}/update'
*/
updateCompany.url = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return updateCompany.definition.url
            .replace('{company}', parsedArgs.company.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::updateCompany
* @see app/Http/Controllers/CompanyController.php:132
* @route '/dashboard/company/{company}/update'
*/
updateCompany.put = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateCompany.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\CompanyController::updateCompany
* @see app/Http/Controllers/CompanyController.php:132
* @route '/dashboard/company/{company}/update'
*/
const updateCompanyForm = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateCompany.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CompanyController::updateCompany
* @see app/Http/Controllers/CompanyController.php:132
* @route '/dashboard/company/{company}/update'
*/
updateCompanyForm.put = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateCompany.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateCompany.form = updateCompanyForm

/**
* @see \App\Http\Controllers\CompanyController::removeCompany
* @see app/Http/Controllers/CompanyController.php:172
* @route '/dashboard/company/{company}/remove'
*/
export const removeCompany = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: removeCompany.url(args, options),
    method: 'put',
})

removeCompany.definition = {
    methods: ["put"],
    url: '/dashboard/company/{company}/remove',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\CompanyController::removeCompany
* @see app/Http/Controllers/CompanyController.php:172
* @route '/dashboard/company/{company}/remove'
*/
removeCompany.url = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return removeCompany.definition.url
            .replace('{company}', parsedArgs.company.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\CompanyController::removeCompany
* @see app/Http/Controllers/CompanyController.php:172
* @route '/dashboard/company/{company}/remove'
*/
removeCompany.put = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: removeCompany.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\CompanyController::removeCompany
* @see app/Http/Controllers/CompanyController.php:172
* @route '/dashboard/company/{company}/remove'
*/
const removeCompanyForm = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeCompany.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\CompanyController::removeCompany
* @see app/Http/Controllers/CompanyController.php:172
* @route '/dashboard/company/{company}/remove'
*/
removeCompanyForm.put = (args: { company: number | { id: number } } | [company: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeCompany.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

removeCompany.form = removeCompanyForm

/**
* @see \App\Http\Controllers\EditionController::dashEditions
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
export const dashEditions = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashEditions.url(options),
    method: 'get',
})

dashEditions.definition = {
    methods: ["get","head"],
    url: '/dashboard/editions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::dashEditions
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
dashEditions.url = (options?: RouteQueryOptions) => {
    return dashEditions.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::dashEditions
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
dashEditions.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: dashEditions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::dashEditions
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
dashEditions.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: dashEditions.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::dashEditions
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
const dashEditionsForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashEditions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::dashEditions
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
dashEditionsForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashEditions.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::dashEditions
* @see app/Http/Controllers/EditionController.php:20
* @route '/dashboard/editions'
*/
dashEditionsForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: dashEditions.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

dashEditions.form = dashEditionsForm

/**
* @see \App\Http\Controllers\EditionController::createEdition
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
export const createEdition = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createEdition.url(options),
    method: 'get',
})

createEdition.definition = {
    methods: ["get","head"],
    url: '/dashboard/edition/create',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::createEdition
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
createEdition.url = (options?: RouteQueryOptions) => {
    return createEdition.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::createEdition
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
createEdition.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: createEdition.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::createEdition
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
createEdition.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: createEdition.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::createEdition
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
const createEditionForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: createEdition.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::createEdition
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
createEditionForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: createEdition.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::createEdition
* @see app/Http/Controllers/EditionController.php:29
* @route '/dashboard/edition/create'
*/
createEditionForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: createEdition.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

createEdition.form = createEditionForm

/**
* @see \App\Http\Controllers\EditionController::storeEdition
* @see app/Http/Controllers/EditionController.php:34
* @route '/dashboard/edition/store'
*/
export const storeEdition = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeEdition.url(options),
    method: 'post',
})

storeEdition.definition = {
    methods: ["post"],
    url: '/dashboard/edition/store',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\EditionController::storeEdition
* @see app/Http/Controllers/EditionController.php:34
* @route '/dashboard/edition/store'
*/
storeEdition.url = (options?: RouteQueryOptions) => {
    return storeEdition.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::storeEdition
* @see app/Http/Controllers/EditionController.php:34
* @route '/dashboard/edition/store'
*/
storeEdition.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: storeEdition.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EditionController::storeEdition
* @see app/Http/Controllers/EditionController.php:34
* @route '/dashboard/edition/store'
*/
const storeEditionForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeEdition.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EditionController::storeEdition
* @see app/Http/Controllers/EditionController.php:34
* @route '/dashboard/edition/store'
*/
storeEditionForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: storeEdition.url(options),
    method: 'post',
})

storeEdition.form = storeEditionForm

/**
* @see \App\Http\Controllers\EditionController::showEdition
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
export const showEdition = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showEdition.url(args, options),
    method: 'get',
})

showEdition.definition = {
    methods: ["get","head"],
    url: '/dashboard/edition/{edition}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::showEdition
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
showEdition.url = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return showEdition.definition.url
            .replace('{edition}', parsedArgs.edition.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::showEdition
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
showEdition.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showEdition.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::showEdition
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
showEdition.head = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showEdition.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::showEdition
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
const showEditionForm = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showEdition.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::showEdition
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
showEditionForm.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showEdition.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::showEdition
* @see app/Http/Controllers/EditionController.php:61
* @route '/dashboard/edition/{edition}'
*/
showEditionForm.head = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: showEdition.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

showEdition.form = showEditionForm

/**
* @see \App\Http\Controllers\EditionController::editEdition
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
export const editEdition = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editEdition.url(args, options),
    method: 'get',
})

editEdition.definition = {
    methods: ["get","head"],
    url: '/dashboard/edition/{edition}/edit',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\EditionController::editEdition
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
editEdition.url = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return editEdition.definition.url
            .replace('{edition}', parsedArgs.edition.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::editEdition
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
editEdition.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: editEdition.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::editEdition
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
editEdition.head = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: editEdition.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\EditionController::editEdition
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
const editEditionForm = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: editEdition.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::editEdition
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
editEditionForm.get = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: editEdition.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\EditionController::editEdition
* @see app/Http/Controllers/EditionController.php:68
* @route '/dashboard/edition/{edition}/edit'
*/
editEditionForm.head = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: editEdition.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

editEdition.form = editEditionForm

/**
* @see \App\Http\Controllers\EditionController::updateEdition
* @see app/Http/Controllers/EditionController.php:75
* @route '/dashboard/edition/{edition}/update'
*/
export const updateEdition = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateEdition.url(args, options),
    method: 'put',
})

updateEdition.definition = {
    methods: ["put"],
    url: '/dashboard/edition/{edition}/update',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\EditionController::updateEdition
* @see app/Http/Controllers/EditionController.php:75
* @route '/dashboard/edition/{edition}/update'
*/
updateEdition.url = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return updateEdition.definition.url
            .replace('{edition}', parsedArgs.edition.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::updateEdition
* @see app/Http/Controllers/EditionController.php:75
* @route '/dashboard/edition/{edition}/update'
*/
updateEdition.put = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: updateEdition.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\EditionController::updateEdition
* @see app/Http/Controllers/EditionController.php:75
* @route '/dashboard/edition/{edition}/update'
*/
const updateEditionForm = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateEdition.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EditionController::updateEdition
* @see app/Http/Controllers/EditionController.php:75
* @route '/dashboard/edition/{edition}/update'
*/
updateEditionForm.put = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: updateEdition.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

updateEdition.form = updateEditionForm

/**
* @see \App\Http\Controllers\EditionController::removeEdition
* @see app/Http/Controllers/EditionController.php:118
* @route '/dashboard/edition/{edition}/remove'
*/
export const removeEdition = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: removeEdition.url(args, options),
    method: 'put',
})

removeEdition.definition = {
    methods: ["put"],
    url: '/dashboard/edition/{edition}/remove',
} satisfies RouteDefinition<["put"]>

/**
* @see \App\Http\Controllers\EditionController::removeEdition
* @see app/Http/Controllers/EditionController.php:118
* @route '/dashboard/edition/{edition}/remove'
*/
removeEdition.url = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions) => {
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

    return removeEdition.definition.url
            .replace('{edition}', parsedArgs.edition.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\EditionController::removeEdition
* @see app/Http/Controllers/EditionController.php:118
* @route '/dashboard/edition/{edition}/remove'
*/
removeEdition.put = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteDefinition<'put'> => ({
    url: removeEdition.url(args, options),
    method: 'put',
})

/**
* @see \App\Http\Controllers\EditionController::removeEdition
* @see app/Http/Controllers/EditionController.php:118
* @route '/dashboard/edition/{edition}/remove'
*/
const removeEditionForm = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeEdition.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

/**
* @see \App\Http\Controllers\EditionController::removeEdition
* @see app/Http/Controllers/EditionController.php:118
* @route '/dashboard/edition/{edition}/remove'
*/
removeEditionForm.put = (args: { edition: number | { id: number } } | [edition: number | { id: number } ] | number | { id: number }, options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: removeEdition.url(args, {
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'PUT',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'post',
})

removeEdition.form = removeEditionForm

/**
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
* @route '/privacy-policy'
*/
privacyPolicy.url = (options?: RouteQueryOptions) => {
    return privacyPolicy.definition.url + queryParams(options)
}

/**
* @see [serialized-closure]:2
* @route '/privacy-policy'
*/
privacyPolicy.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: privacyPolicy.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/privacy-policy'
*/
privacyPolicy.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: privacyPolicy.url(options),
    method: 'head',
})

/**
* @see [serialized-closure]:2
* @route '/privacy-policy'
*/
const privacyPolicyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: privacyPolicy.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/privacy-policy'
*/
privacyPolicyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: privacyPolicy.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
* @route '/terms-of-service'
*/
termsOfService.url = (options?: RouteQueryOptions) => {
    return termsOfService.definition.url + queryParams(options)
}

/**
* @see [serialized-closure]:2
* @route '/terms-of-service'
*/
termsOfService.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: termsOfService.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/terms-of-service'
*/
termsOfService.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: termsOfService.url(options),
    method: 'head',
})

/**
* @see [serialized-closure]:2
* @route '/terms-of-service'
*/
const termsOfServiceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: termsOfService.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/terms-of-service'
*/
termsOfServiceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: termsOfService.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
* @route '/cookie-policy'
*/
cookiePolicy.url = (options?: RouteQueryOptions) => {
    return cookiePolicy.definition.url + queryParams(options)
}

/**
* @see [serialized-closure]:2
* @route '/cookie-policy'
*/
cookiePolicy.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: cookiePolicy.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/cookie-policy'
*/
cookiePolicy.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: cookiePolicy.url(options),
    method: 'head',
})

/**
* @see [serialized-closure]:2
* @route '/cookie-policy'
*/
const cookiePolicyForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cookiePolicy.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/cookie-policy'
*/
cookiePolicyForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: cookiePolicy.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
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
* @see [serialized-closure]:2
* @route '/settings/appearance'
*/
appearance.url = (options?: RouteQueryOptions) => {
    return appearance.definition.url + queryParams(options)
}

/**
* @see [serialized-closure]:2
* @route '/settings/appearance'
*/
appearance.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: appearance.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/settings/appearance'
*/
appearance.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: appearance.url(options),
    method: 'head',
})

/**
* @see [serialized-closure]:2
* @route '/settings/appearance'
*/
const appearanceForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: appearance.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
* @route '/settings/appearance'
*/
appearanceForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: appearance.url(options),
    method: 'get',
})

/**
* @see [serialized-closure]:2
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
