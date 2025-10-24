import Auth from './Auth'
import DashboardController from './DashboardController'
import CompanyController from './CompanyController'
import EditionController from './EditionController'
import Settings from './Settings'

const Controllers = {
    Auth: Object.assign(Auth, Auth),
    DashboardController: Object.assign(DashboardController, DashboardController),
    CompanyController: Object.assign(CompanyController, CompanyController),
    EditionController: Object.assign(EditionController, EditionController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers