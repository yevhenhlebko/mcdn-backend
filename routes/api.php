<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Authentication api routes
Route::post('auth/check', 'UserController@check');
Route::post('auth/signin', 'UserController@login');
Route::post('auth/signup', 'UserController@register');
Route::post('auth/password-reset', 'UserController@passwordReset');

Route::middleware('auth')->group(function () {
    Route::get('/auth/logout', 'UserController@logout');
	Route::post('/auth/update-password', 'UserController@updatePassword');
	Route::get('/auth/user', 'UserController@getUser');
});

Route::middleware('auth')->group(function () {
	Route::post('/profile/timezone', 'UserController@updateTimezone');
	Route::get('/profile/timezones', 'UserController@getTimezones');
});

// Route::group(['prefix' => 'locations'], function () {
// 	Route::get('/', 'LocationController@index')->middleware('auth:acs_admin,acs_manager,acs_viewer,customer_admin,customer_manager');
// 	Route::post('/add', 'LocationController@store')->middleware('auth:customer_admin,customer_manager');
// 	Route::patch('/update', 'LocationController@update')->middleware('auth:customer_admin,customer_manager');
// });

// Route::group(['prefix' => 'zones'], function () {
// 	Route::get('/', 'ZoneController@index')->middleware('auth:acs_admin,acs_manager,acs_viewer,customer_admin,customer_manager,customer_operator');
// 	Route::post('/add', 'ZoneController@store')->middleware('auth:customer_admin,customer_manager');
// 	Route::patch('/update', 'ZoneController@update')->middleware('auth:customer_admin,customer_manager');
// });

Route::apiResource('locations', LocationController::class)->only(['update', 'index', 'store'])->middleware('auth');
Route::apiResource('zones', ZoneController::class)->only(['update', 'index', 'store'])->middleware('auth');
Route::apiResource('materials', MaterialController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('auth');

Route::apiResource('material-locations', MaterialLocationController::class)->only(['index', 'store', 'update', 'destroy'])->middleware('auth');

Route::group(['prefix' => 'materials'], function () {
	Route::post('/report', 'MaterialController@getMaterialReport');
	Route::delete('/report/{id}', 'MaterialController@deleteReport');
	Route::post('/blenders', 'MaterialController@getBlenders');
	Route::post('/export', 'MaterialController@exportReport');

	Route::post('/system-inventory-report', 'MaterialController@getSystemInventoryReport')->middleware('auth:customer_admin');
	Route::post('/system-inventory-report/export', 'MaterialController@exportSystemInventoryReport')->middleware('auth:customer_admin');

	Route::post('/system-inventory-report-data', 'MaterialController@getSystemInventoryReportData')->middleware('auth:customer_admin');
	Route::post('/system-inventory-report-data/export', 'MaterialController@exportSystemInventoryReportData')->middleware('auth:customer_admin');
});

Route::apiResource('configurations', ConfigurationController::class)->only(['show', 'update', 'index'])->middleware('auth');

// Route::group(['prefix' => 'configurations'], function () {
// 	Route::get('/', 'ConfigurationController@getConfigurationNames');
// 	Route::get('/{id}', 'ConfigurationController@getConfiguration')->middleware('auth:super_admin');
// 	Route::patch('/{id}', 'ConfigurationController@update')->middleware('auth:super_admin');
// });

Route::group(['prefix' => 'devices'], function () {
	Route::get('/{id}/configuration', 'DeviceController@getDeviceConfiguration');
	Route::get('/customer-devices', 'DeviceController@getCustomerDevices')->middleware('auth:customer_admin,customer_manager,customer_operator');
	Route::get('/all', 'DeviceController@getAllDevices')->middleware('auth:acs_admin,acs_manager,acs_viewer');
	Route::post('/toggle-active-devices', 'DeviceController@toggleActiveDevices')->middleware('auth:acs_admin,acs_manager,acs_viewer');
	Route::post('/devices-analytics', 'DeviceController@getDevicesAnalytics')->middleware('auth:customer_admin,customer_manager,customer_operator,acs_admin,acs_manager,acs_viewer');
	Route::post('/saved-machines', 'DeviceController@getSavedMachines')->middleware('auth:customer_admin,customer_manager,customer_operator,acs_admin,acs_manager,acs_viewer');
	Route::post('/assign-zone', 'DeviceController@updateCustomerDevice')->middleware('auth:customer_admin,customer_manager,customer_operator');
});

Route::group(['prefix' => 'downtime-plans'], function () {
	Route::get('/', 'DowntimePlanController@index')->middleware('auth:customer_admin,customer_manager,customer_operator');
	Route::post('/store', 'DowntimePlanController@store')->middleware('auth:customer_admin,customer_manager,customer_operator');
	Route::post('/update/{id}', 'DowntimePlanController@update')->middleware('auth:customer_admin,customer_manager,customer_operator');
});

Route::apiResource('users', UserController::class)->only(['edit', 'update', 'index', 'store'])->middleware('auth');
Route::post('users/delete', 'UserController@deleteUser');

Route::group(['prefix' => 'app-settings'], function () {
	Route::post('/grab-colors', 'SwatchController@grabColors');
	Route::post('/get-setting', 'SettingController@getSetting');
	Route::post('/website-colors', 'SettingController@applyWebsiteColors');
	Route::post('/upload-logo', 'SettingController@uploadLogo');
	Route::post('/upload-image', 'SettingController@uploadImage');
	Route::post('/update-auth-background', 'SettingController@updateAuthBackground');
	Route::post('/reset', 'SettingController@resetSettings');
	Route::post('/set-product-info', 'SettingController@setProductInfo');
	Route::post('/set-page-title', 'SettingController@setPageTitle');
});

Route::group(['prefix' => 'dashboard'], function () {
	Route::post('/init-locations-table', 'MachineController@getLocationsTableData');
	Route::get('/init-zones-table/{id}', 'MachineController@getZonesTableData');
	Route::get('/init-machines-table/{id}', 'MachineController@getMachinesTableData');
	Route::post('/devices-for-dashboard-table', 'DeviceController@getDashboardMachinesTable');
});

Route::group(['prefix' => 'customers'], function () {
	Route::post('/add', 'CompanyController@addCustomer')->middleware('auth:acs_admin,acs_manager');
	Route::get('/{id}', 'CompanyController@getCustomer')->middleware('auth:acs_admin,acs_manager');
	Route::post('/update-account/{id}', 'CompanyController@updateCustomerAccount')->middleware('auth:acs_admin,acs_manager');
	Route::post('/update-profile/{id}', 'CompanyController@updateCustomerProfile')->middleware('auth:acs_admin,acs_manager');
});

Route::group(['prefix' => 'companies'], function () {
	Route::get('/admins', 'CompanyController@getCompanyAdmins')->middleware('auth:acs_admin,acs_manager,acs_viewer');
	Route::get('/', 'CompanyController@index')->middleware('auth:acs_admin,acs_manager,acs_viewer');
});

Route::group(['prefix' => 'devices'], function () {
	Route::post('/', 'DeviceController@getACSDevices')->middleware('auth:acs_admin,acs_manager,acs_viewer');
	Route::post('/import', 'DeviceController@importDevices')->middleware('auth:acs_admin,acs_manager');
	Route::post('/device-assigned', 'DeviceController@deviceAssigned')->middleware('auth:acs_admin,acs_manager');
	Route::post('/suspend-device', 'DeviceController@suspendDevice')->middleware('auth:acs_admin,acs_manager');
	Route::post('/device-configuration', 'DeviceController@sendDeviceConfiguration')->middleware('auth:acs_admin,acs_manager');
	Route::post('/enabled-properties', 'DeviceController@updateEnabledProperties');

	Route::get('/query-sim/{iccid}', 'DeviceController@querySIM')->middleware('auth:acs_admin,acs_manager');
	Route::get('/suspend-sim/{iccid}', 'DeviceController@suspendSIM')->middleware('auth:acs_admin,acs_manager');
	Route::get('/remote-web/{deviceid}', 'DeviceController@remoteWeb')->middleware('auth:acs_admin,acs_manager');
	Route::get('/remote-cli/{deviceid}', 'DeviceController@remoteCli')->middleware('auth:acs_admin,acs_manager');
});

Route::group(['prefix' => 'machine-tags'], function () {
	Route::get('/{id}', 'MachineTagController@getMachineTags');
});

Route::group(['prefix' => 'machines'], function () {
	Route::post('/generate-machines-report', 'MachineController@generateMachinesReport');
	Route::get('/get-machines-report', 'MachineController@getMachinesReport');
	Route::delete('/report/{id}', 'MachineController@deleteMachinesReport');
	Route::post('/get-blender-weights', 'MachineController@getBlenderWeights');
	Route::post('/get-alarm-history', 'AlarmController@getProductAlarmHistory');
});

Route::group(['prefix' => 'analytics'], function () {
	Route::post('/get-machine-tags', 'MachineTagController@getMachinesTags');
	Route::post('/get-machines', 'MachineController@getMachines');
	Route::post('/data-tool-series', 'MachineController@getDataToolSeries');
	Route::post('/request-service', 'MachineController@requestService');
	Route::post('/save-machine', 'MachineController@saveMachine');
	Route::post('/get-saved-status', 'MachineController@getSavedStatus');
	Route::post('/product-overview', 'MachineController@getProductOverview');
	Route::post('/product-utilization', 'MachineController@getProductUtilization');
	Route::post('/product-energy-consumption', 'MachineController@getEnergyConsumption');
	Route::post('/product-inventory', 'MachineController@getInventories');
	Route::post('/product-station-conveyings', 'MachineController@getStationConveyings');
	Route::get('/weekly-running-hours/{id}', 'MachineController@getWeeklyRunningHours');
	Route::post('/product-weight', 'MachineController@getProductWeight');
	Route::post('/product-current-recipe', 'MachineController@getCurrentRecipe');
	Route::post('/blender/process-rate', 'MachineController@getBlenderProcessRate');
	Route::post('/blender/capability', 'MachineController@getBlenderCapabilities');
	Route::post('/blender/inventory-material', 'MachineController@updateInventoryMaterial');
	Route::post('/blender/toggle-in-progress', 'MachineController@updateTrackingStatus');
	Route::post('/blender/calibration-factors', 'MachineController@getBDBlenderCalibrationFactors');
	Route::post('/blender/load-cells', 'MachineController@getLoadCells');
	Route::post('/accumeter/blender-capabilities', 'MachineController@getBlenderCapabilities');
	Route::post('/accumeter/feeder-calibrations', 'MachineController@getFeederCalibrations');
	Route::post('/accumeter/feeder-speeds', 'MachineController@getFeederSpeeds');
	Route::post('/accumeter/target-rate', 'MachineController@getTargetRate');
	Route::post('/accumeter/recipe', 'MachineController@getTgtActualRecipes');
	Route::post('/product-system-states', 'MachineController@getProductStates');
	Route::post('/product-hopper-stables', 'MachineController@getHopperStables');
	Route::post('/product-system-states-3', 'MachineController@getMachineStates3');
	Route::post('/product-feeder-stables', 'MachineController@getFeederStables');
	Route::post('/product-production-rate', 'MachineController@getProductProcessRate');
	Route::post('/product-hopper-inventories', 'MachineController@getInventories3');
	Route::post('/product-hauloff-lengths', 'MachineController@getHauloffLengths');
	Route::post('/vtc-plus/pump-onlines', 'MachineController@getPumpOnlines');
	Route::post('/vtc-plus/pump-blowbacks', 'MachineController@getPumpBlowBacks');
	Route::post('/vtc-plus/pump-hours-oil', 'MachineController@getPumpHoursOil');
	Route::post('/vtc-plus/pump-hours', 'MachineController@getPumpHours');
	Route::post('/vtc-plus/pump-online-life', 'MachineController@getPumpOnlineLife');
	Route::post('/product-drying-hopper-states', 'MachineController@getDryingHopperStates');
	Route::post('/product-hopper-temperatures', 'MachineController@getHopperTemperatures');

	Route::post('/ngx-dryer/bed-states', 'MachineController@getNgxDryerBedStates');
	Route::post('/ngx-dryer/online-hours', 'MachineController@getNgxDryerOnlineHours');
	Route::post('/ngx-dryer/dew-point-temperature', 'MachineController@getDewPointTemperature');
	Route::post('/ngx-dryer/region-air-temperature', 'MachineController@getRegionAirTemperature');

	Route::post('/ngx-nomad-dryer/hopper-states', 'MachineController@getNomadHopperStates');

	Route::post('/tcu/actual-target-temperature', 'MachineController@getTcuActTgtTemperature');

	Route::post('/portable-chiller/process-out-temperature', 'MachineController@getProcessOutTemperature');

	Route::post('/central-chiller/chiller-in-out-temperature', 'MachineController@getCentralChillerTemperature');

	Route::post('/t50-granulator/runnings', 'MachineController@getT50Runnings');
	Route::post('/t50-granulator/hours', 'MachineController@getT50Hours');
	Route::post('/t50-granulator/bearing-temp', 'MachineController@getT50BearingTemp');
	Route::post('/t50-granulator/amps', 'MachineController@getT50Amps');
});

Route::group(['prefix' => 'notes', 'middleware' => 'auth'], function () {
	Route::post('/', 'NoteController@store');
	Route::get('/{device_id}', 'NoteController@index');
});

Route::group(['prefix' => 'alarms'], function () {
	Route::post('/alarms-for-customer-devices', 'AlarmController@getAlarmsForCustomerDevices');
	Route::post('/', 'AlarmController@getProductAlarms');
	Route::post('/overview', 'AlarmController@getAlarmsOverview');
	Route::post('/per-company-configuration', 'AlarmController@getAlarmsForCompany');
	Route::get('/alarms-by-company-id/{company_id}', 'AlarmController@getAlarmsByCompanyId');
	Route::post('/severity-by-company-id', 'AlarmController@getSeverityByCompanyId');
	Route::post('/alarms-per-type-by-machine', 'AlarmController@getAlarmsPerTypeByMachine');
	Route::post('/alarms-distribution-by-machine', 'AlarmController@getAlarmsDistributionByMachine');
	Route::post('/alarms-amount-per-machine-by-company-id', 'AlarmController@getAlarmsAmountPerMachineByCompanyId');
	Route::post('/alarms-reports', 'AlarmController@getAlarmsReports');
});

Route::group(['prefix' => 'cities'], function () {
	Route::get('/{state}', 'CityController@citiesForState');
});

Route::group(['prefix' => 'settings'], function () {
	Route::get('/app-settings', 'SettingController@appSettings');
});

Route::group(['prefix' => 'thresholds'], function () {
	Route::get('/', 'ThresholdController@getThresholdList');
	Route::get('/active', 'ThresholdController@getActiveThresholds');
	Route::post('/add', 'ThresholdController@addThreshold');
	Route::post('/change-status/{id}', 'ThresholdController@changeStatus');
	Route::delete('/{id}', 'ThresholdController@deleteThreshold');
	Route::put('/{id}', 'ThresholdController@updateThreshold');
	Route::post('/clear', 'ThresholdController@clearThresholdStatus');
	Route::get('/approaching', 'ThresholdController@getApproachingThresholds');
	Route::post('/clear-approaching', 'ThresholdController@clearApproachingStatus');
});

Route::post('test/send-mail', 'CompanyController@testMail');
Route::post('test/send-sms', 'CompanyController@testSMS');
Route::post('test/blender-json', 'TestController@store');

Route::post('test/azure', 'DeviceController@testAzureJson');
Route::post('test', 'DeviceController@testFunction');
Route::post('test/carrier/{id}', 'DeviceController@carrierFromKoreAPI');

Route::get('test/pusher-notification', 'DeviceController@sendEvent');
