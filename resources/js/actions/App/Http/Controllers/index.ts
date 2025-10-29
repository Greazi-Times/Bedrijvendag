import Auth from './Auth'
import EditionController from './EditionController'
import DashboardController from './DashboardController'
import CompanyController from './CompanyController'
import Settings from './Settings'

const Controllers = {
    Auth: Object.assign(Auth, Auth),
    EditionController: Object.assign(EditionController, EditionController),
    DashboardController: Object.assign(DashboardController, DashboardController),
    CompanyController: Object.assign(CompanyController, CompanyController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers