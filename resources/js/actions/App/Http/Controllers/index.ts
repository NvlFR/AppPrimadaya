import Auth from './Auth'
import DashboardController from './DashboardController'
import TransactionController from './TransactionController'
import ServiceController from './ServiceController'
import CustomerController from './CustomerController'
import ExpenseController from './ExpenseController'
import StockController from './StockController'
import ReportController from './ReportController'
import UserController from './UserController'
import Settings from './Settings'

const Controllers = {
    Auth: Object.assign(Auth, Auth),
    DashboardController: Object.assign(DashboardController, DashboardController),
    TransactionController: Object.assign(TransactionController, TransactionController),
    ServiceController: Object.assign(ServiceController, ServiceController),
    CustomerController: Object.assign(CustomerController, CustomerController),
    ExpenseController: Object.assign(ExpenseController, ExpenseController),
    StockController: Object.assign(StockController, StockController),
    ReportController: Object.assign(ReportController, ReportController),
    UserController: Object.assign(UserController, UserController),
    Settings: Object.assign(Settings, Settings),
}

export default Controllers