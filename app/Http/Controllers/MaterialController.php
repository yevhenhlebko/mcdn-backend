<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;

use App\Material;
use App\MaterialLocation;
use App\DeviceData;
use App\MaterialTrack;
use App\TeltonikaConfiguration;
use App\InventoryMaterial;
use App\Device;
use App\SystemInventory;

use \stdClass;
use File;

use App\Exports\ReportExport;
use App\Exports\SystemInventoryReportExport;
use Maatwebsite\Excel\Facades\Excel;

class MaterialController extends Controller
{
    public function index(Request $request) {
    	$user = $request->user('api');

    	$company = $user->company;

		$materials = $company->materials;

		return response()->json(compact('materials'));
	}

	public function store(Request $request) {
		$validator = Validator::make($request->all(), [ 
            'material' => 'required',
        ]);

        if ($validator->fails())
        {
            return response()->json(['error'=>$validator->errors()], 422);            
        }

        $user = $request->user('api');

    	$company = $user->company;

		$company->materials()->create([
			'material' => $request->material,
		]);

		return response()->json('Created Successfully');
	}

	public function update(Request $request, Material $material) {
		$validator = Validator::make($request->all(), [ 
            'material' => 'required',
        ]);

        if ($validator->fails())
        {
            return response()->json(['error'=>$validator->errors()], 422);            
        }

        $material->update([
        	'material' => $request->material,
        ]);

		return response()->json('Updated Successfully');
	}

	public function destroy(Material $material) {
		$material->delete();

		return response()->json('Deleted Successfully');
	}

    public function getBlenders(Request $request) {
        $user = $request->user('api');
        $company = $user->company;
        $inventory_materials = $company->inventoryMaterials();
        $inventory_material_plc_ids = $inventory_materials->pluck('plc_id');

        $teltonika_ids = TeltonikaConfiguration::whereIn('plc_serial_number', $inventory_material_plc_ids)->pluck('teltonika_id');

        $blenders = $company->devices()->select('customer_assigned_name', 'id', 'serial_number')->whereIn('serial_number', $teltonika_ids)->get();

        return response()->json(compact('blenders'));
    }

    public function getMaterialReport(Request $request) {
        $device = Device::where('serial_number', $request->blenderId)->first();
        $serial_number = $device->teltonikaConfiguration->plcSerialNumber();
        $inventoryMaterial = InventoryMaterial::where('plc_id', $serial_number)->first();
        $tracks = $inventoryMaterial->materialTracks;

        $materials = Material::get();
        $locations = MaterialLocation::get();

        $material_keys = ['material1_id', 'material2_id', 'material3_id', 'material4_id', 'material5_id', 'material6_id', 'material7_id', 'material8_id'];
        $location_keys = ['location1_id', 'location2_id', 'location3_id', 'location4_id', 'location5_id', 'location6_id', 'location7_id', 'location8_id'];

        foreach ($tracks as $key => &$track) {
            $hop_material1 = DeviceData::where('serial_number', $serial_number)
                ->where('tag_id', 15)
                ->where('timestamp', '<', $track->start)
                ->latest('timedata')
                ->first();

            $hop_material2 = DeviceData::where('serial_number', $serial_number)
                ->where('tag_id', 15)
                ->where('timestamp', '<', $track->stop)
                ->latest('timedata')
                ->first();

            $actual_material1 = DeviceData::where('serial_number', $serial_number)
                ->where('tag_id', 16)
                ->where('timestamp', '<', $track->start)
                ->latest('timedata')
                ->first();

            $actual_material2 = DeviceData::where('serial_number', $serial_number)
                ->where('tag_id', 16)
                ->where('timestamp', '<', $track->stop)
                ->latest('timedata')
                ->first();

            $initial_materials = (array)json_decode($track->initial_materials);

            $reportItems = [];

            for ($i=0; $i < 8; $i++) {
                $material_id = $initial_materials[$material_keys[$i]];
                $location_id = $initial_materials[$location_keys[$i]];

                if ($material_id) {
                    $item = new stdClass();

                    $material = $materials->where('id', $material_id)->first();
                    $item->material = $material ? $material->material : '';

                    $location = $locations->where('id', $location_id)->first();
                    $item->location = $location ? $location->location : '';

                    $item->value = (json_decode($hop_material2->values)[$i] + json_decode($actual_material2->values)[$i] / 1000) -
                        (json_decode($hop_material1->values)[$i] + json_decode($actual_material1->values)[$i] / 1000);

                    $item->value = round($item->value, 3);
                    array_push($reportItems, $item);
                }
            }
            
            $track->reportItems = $reportItems;
        }

        return response()->json(compact('tracks'));
    }

    public function deleteReport(Request $request) {
        $report = MaterialTrack::findOrFail($request->id);

        $report->delete();

        return response()->json('Successfully Deleted');
    }

    public function exportReport(Request $request) {
        $track = MaterialTrack::findOrFail($request->id);

        // $device = Device::where('serial_number', $request->blenderId)->first();
        // $serial_number = $device->teltonikaConfiguration->plcSerialNumber();
        $serial_number = $track->inventoryMaterial->teltonika->plc_serial_number;

        $materials = Material::get();
        $locations = MaterialLocation::get();

        $material_keys = ['material1_id', 'material2_id', 'material3_id', 'material4_id', 'material5_id', 'material6_id', 'material7_id', 'material8_id'];
        $location_keys = ['location1_id', 'location2_id', 'location3_id', 'location4_id', 'location5_id', 'location6_id', 'location7_id', 'location8_id'];

        $hop_material1 = DeviceData::where('serial_number', $serial_number)
            ->where('tag_id', 15)
            ->where('timestamp', '<', $track->start)
            ->latest('timedata')
            ->first();

        $hop_material2 = DeviceData::where('serial_number', $serial_number)
            ->where('tag_id', 15)
            ->where('timestamp', '<', $track->stop)
            ->latest('timedata')
            ->first();

        $actual_material1 = DeviceData::where('serial_number', $serial_number)
            ->where('tag_id', 16)
            ->where('timestamp', '<', $track->start)
            ->latest('timedata')
            ->first();

        $actual_material2 = DeviceData::where('serial_number', $serial_number)
            ->where('tag_id', 16)
            ->where('timestamp', '<', $track->stop)
            ->latest('timedata')
            ->first();

        $initial_materials = (array)json_decode($track->initial_materials);

        $reportItems = [];

        for ($i=0; $i < 8; $i++) {
            $material_id = $initial_materials[$material_keys[$i]];
            $location_id = $initial_materials[$location_keys[$i]];

            if ($material_id) {
                $item = [];

                $material = $materials->where('id', $material_id)->first();
                $item['material'] = $material ? $material->material : '';

                $location = $locations->where('id', $location_id)->first();
                $item['location'] = $location ? $location->location : '';

                $item['value'] = (json_decode($hop_material2->values)[$i] + json_decode($actual_material2->values)[$i] / 1000) -
                    (json_decode($hop_material1->values)[$i] + json_decode($actual_material1->values)[$i] / 1000);

                $item['value'] = round($item['value'], 3);
                array_push($reportItems, $item);
            }
        }

        // $filename = $device->customer_assigned_name .
        //             date('Y-m-d H-i-s', $track->start) .
        //             ' - ' .
        //             date('Y-m-d H-i-s', $track->stop) .
        //             '.xlsx';

        $filename = 'Blender - ' . $track->id . '.xlsx';

        Excel::store(new ReportExport($reportItems), 'report.xlsx');
        File::move(storage_path('app/report.xlsx'), public_path('assets/app/reports/' . $filename));

        return response()->json([
            'mesage' => 'Successfully exported',
            'filename' => $filename
        ]);
    }

    public function getSystemInventoryReport(Request $request) {
        $keyed_materials = $this->helperGetSystemInventoryReport($request);

        return response()->json(compact('keyed_materials'));
    }

    public function helperGetSystemInventoryReport(Request $request) {
        $user = $request->user('api');
        $company = $user->company;
        $materials = $company->materials;
        $inventory_materials = $company->inventoryMaterials;

        // 1. change materials to useful keyed array values
        $keyed_materials = $materials->mapWithKeys(function ($material) {
            return [
                $material['id'] => [
                    'material' => $material['material'],
                    'value' => 0,
                ],
            ];
        })->all();

        // 2. calculate inventories for each material till now
        foreach ($inventory_materials as $key => $inventory_material) {
            $system_inventories_for_inventory_material = $inventory_material->systemInventories;

            foreach ($system_inventories_for_inventory_material as $system_inventory) {
                $last_material = $system_inventories_for_inventory_material->where('hopper_id', $system_inventory->hopper_id)
                    ->where('created_at', '<', $system_inventory->created_at)
                    ->sortByDesc('created_at')
                    ->first();

                if ($last_material)
                    $keyed_materials[$last_material->material_id]['value'] += ($system_inventory->inventory - $last_material->inventory);
            }
            
            // 3. account current inventory into materials inventory
            $hop_material = DeviceData::where('serial_number', $inventory_material->plc_id)
                ->where('tag_id', 15)
                ->latest('timedata')
                ->first();
            $actual_material = DeviceData::where('serial_number', $inventory_material->plc_id)
                ->where('tag_id', 16)
                ->latest('timedata')
                ->first();

            if ($inventory_material->material1_id) {
                $current_inventory_for_material_1 = json_decode($hop_material->values)[0] + json_decode($actual_material->values)[0] / 1000;
                $last_material = $system_inventories_for_inventory_material->where('hopper_id', 0)
                    ->sortByDesc('created_at')
                    ->first();
                if ($last_material)
                    $keyed_materials[$inventory_material->material1_id]['value'] += ($current_inventory_for_material_1 - $last_material->inventory);
            }

            if ($inventory_material->material2_id) {
                $current_inventory_for_material_2 = json_decode($hop_material->values)[1] + json_decode($actual_material->values)[1] / 1000;
                $last_material = $system_inventories_for_inventory_material->where('hopper_id', 1)
                    ->sortByDesc('created_at')
                    ->first();
                if ($last_material)
                    $keyed_materials[$inventory_material->material2_id]['value'] += ($current_inventory_for_material_2 - $last_material->inventory);
            }

            if ($inventory_material->material3_id) {
                $current_inventory_for_material_3 = json_decode($hop_material->values)[2] + json_decode($actual_material->values)[2] / 1000;
                $last_material = $system_inventories_for_inventory_material->where('hopper_id', 2)
                    ->sortByDesc('created_at')
                    ->first();
                if ($last_material)
                    $keyed_materials[$inventory_material->material3_id]['value'] += ($current_inventory_for_material_3 - $last_material->inventory);
            }

            if ($inventory_material->material4_id) {
                $current_inventory_for_material_4 = json_decode($hop_material->values)[3] + json_decode($actual_material->values)[3] / 1000;
                $last_material = $system_inventories_for_inventory_material->where('hopper_id', 3)
                    ->sortByDesc('created_at')
                    ->first();
                if ($last_material)
                    $keyed_materials[$inventory_material->material4_id]['value'] += ($current_inventory_for_material_4 - $last_material->inventory);
            }

            if ($inventory_material->material5_id) {
                $current_inventory_for_material_5 = json_decode($hop_material->values)[4] + json_decode($actual_material->values)[4] / 1000;
                $last_material = $system_inventories_for_inventory_material->where('hopper_id', 4)
                    ->sortByDesc('created_at')
                    ->first();
                if ($last_material)
                    $keyed_materials[$inventory_material->material5_id]['value'] += ($current_inventory_for_material_5 - $last_material->inventory);
            }

            if ($inventory_material->material6_id) {
                $current_inventory_for_material_6 = json_decode($hop_material->values)[5] + json_decode($actual_material->values)[5] / 1000;
                $last_material = $system_inventories_for_inventory_material->where('hopper_id', 5)
                    ->sortByDesc('created_at')
                    ->first();
                if ($last_material)
                    $keyed_materials[$inventory_material->material6_id]['value'] += ($current_inventory_for_material_6 - $last_material->inventory);
            }

            if ($inventory_material->material7_id) {
                $current_inventory_for_material_7 = json_decode($hop_material->values)[6] + json_decode($actual_material->values)[6] / 1000;
                $last_material = $system_inventories_for_inventory_material->where('hopper_id', 6)
                    ->sortByDesc('created_at')
                    ->first();
                if ($last_material)
                    $keyed_materials[$inventory_material->material7_id]['value'] += ($current_inventory_for_material_7 - $last_material->inventory);
            }

            if ($inventory_material->material8_id) {
                $current_inventory_for_material_8 = json_decode($hop_material->values)[7] + json_decode($actual_material->values)[7] / 1000;
                $last_material = $system_inventories_for_inventory_material->where('hopper_id', 7)
                    ->sortByDesc('created_at')
                    ->first();
                if ($last_material)
                    $keyed_materials[$inventory_material->material8_id]['value'] += ($current_inventory_for_material_8 - $last_material->inventory);
            }
        }

        return $keyed_materials;
        // return response()->json(compact('keyed_materials'));
    }

    public function exportSystemInventoryReport(Request $request) {
        $keyed_materials = $this->helperGetSystemInventoryReport($request);

        $filename = $request->user('api')->company->name . ' - System Inventory Report' . '.xlsx';

        Excel::store(new SystemInventoryReportExport($keyed_materials), 'report.xlsx');
        File::move(storage_path('app/report.xlsx'), public_path('assets/app/reports/' . $filename));

        return response()->json([
            'mesage' => 'Successfully exported',
            'filename' => $filename
        ]);
    }
}