import Auth from './Auth'
import WelcomeController from './WelcomeController'
import EditionController from './EditionController'
import CompanyController from './CompanyController'
import BorrelController from './BorrelController'
import DashboardController from './DashboardController'
import StandController from './StandController'
import StandPdfController from './StandPdfController'
import Settings from './Settings'

const Controllers = {
    Auth: Object.assign(Auth, Auth),
    WelcomeController: Object.assign(WelcomeController, WelcomeController),
    EditionController: Object.assign(EditionController, EditionController),
    CompanyController: Object.assign(CompanyController, CompanyController),
    BorrelController: Object.assign(BorrelController, BorrelController),
    DashboardController: Object.assign(DashboardController, DashboardController),
    StandController: Object.assign(StandController, StandController),
    StandPdfController: Object.assign(StandPdfController, StandPdfController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers