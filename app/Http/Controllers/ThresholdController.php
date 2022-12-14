<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Threshold;
use App\MachineTag;
use App\AlarmType;
use App\Device;

use \stdClass;

class ThresholdController extends Controller
{
    public function addThreshold(Request $request) {
        $user = $request->user('api');
        $conditions = $request->conditions;
        $condition_name = [];
        $device = Device::where('device_id', $request->deviceId)->first();

        foreach ($conditions as $key => $condition) {
            $tag = MachineTag::where('configuration_id', $device->machine_id)->where('id', $condition['telemetry'])->first();

            if (!$tag) {
                $tag = AlarmType::where('machine_id', $device->machine_id)->where('id', $condition['telemetry'])->first();
            }

            $multipled_by = isset($tag['divided_by']) ? $tag['divided_by'] : 1;
            $bytes = isset($tag['bytes']) ? $tag['bytes'] : 0;
            $offset = $tag['offset'] ? $tag['offset'] : 0;

            $option = Threshold::where('tag_id', $tag->tag_id)
                                ->where('offset', $offset)
                                ->where('operator', $condition['operator'])
                                ->where('value', $condition['value'])
                                ->where('approaching', $condition['approachingValue'])
                                ->where('device_id', $request->deviceId)
                                ->where('user_id', $user->id)
                                ->first();

            if ($option) {
                return response()->json([
                    'message' => 'Condition with same operator already exists',
                    'status' => 'fail'
                ]);
            }

            Threshold::create([
                'user_id' => $user->id,
                'device_id' => $request->deviceId,
                'tag_id' => $tag->tag_id,
                'offset' => $offset,
                'operator' => $condition['operator'],
                'value' => $condition['value'],
                'sms_info' => json_encode($request->smsForm),
                'email_info' => json_encode($request->emailForm),
                'serial_number' => $device->serial_number,
                'multipled_by' => $multipled_by,
                'bytes' => $bytes,
                'approaching' => $condition['approachingValue']
            ]);
        }

        return response()->json([
            'message' => 'Threshold added successfully',
            'status' => 'success'
        ]);
    }

    public function getThresholdList(Request $request) {
        $user = $request->user('api');
        if ($user->hasRole(['customer_admin'])) {
            $conditions = Threshold::all();
        } else {
            $conditions = Threshold::where('user_id', $user->id)->get();
        }

        foreach ($conditions as $key => $condition) {
            $machine_id = Device::where('device_id', $condition['device_id'])->first()->machine_id;

            $tag = MachineTag::where('configuration_id', $machine_id)->where('tag_id', $condition['tag_id'])->where('offset', $condition['offset'])->first();

            if (!$tag) {
                $tag = AlarmType::where('machine_id', $machine_id)->where('tag_id', $condition['tag_id'])->where('offset', $condition['offset'])->first();
            }

            $condition['tag_name'] = $tag->name;
            $condition['option'] = $tag->name . " " . $this->getMathExpressionFromString($condition['operator']). " " . $condition['value'];
        }

        return response()->json(compact('conditions'));
    }

    public function changeStatus(Request $request, $id) {
        $threshold = Threshold::where('id', $id)->first();

        $threshold->update([
            'status' => !$threshold->status
        ]);
    }

    public function deleteThreshold($id) {
        $threshold = Threshold::where('id', $id)->first();

        if ($threshold) {
            $threshold->delete();
            $status = true;
            $message = 'Threshold deleted successfully';
        } else {
            $status = false;
            $message = 'Threshold does not exist';
        }

        return response()->json([
            'message' => $message,
            'status' => $status
        ]);
    }

    public function updateThreshold(Request $request) {
        $threshold = Threshold::where('id', $request->id);

        $threshold->update([
            'operator' => $request->condition['operator'],
            'value' => $request->condition['value'],
            'approaching' => $request->condition['approaching']
        ]);

        return response()->json([
            'message' => 'Threshold updated successfully'
        ]);
    }

    public function getActiveThresholds(Request $request) {
        $user = $request->user('api');

        if ($user->hasRole(['customer_admin'])) {
            $conditions = Threshold::where('message_status', true)->get();
        } else {
            $conditions = Threshold::where('user_id', $user->id)->where('message_status', true)->get();
        }

        foreach ($conditions as $key => $condition) {
            $device = Device::where('device_id', $condition['device_id'])->first();

            $tag = MachineTag::where('configuration_id', $device->machine_id)->where('tag_id', $condition['tag_id'])->where('offset', $condition['offset'])->first();

            if (!$tag) {
                $tag = AlarmType::where('machine_id', $device->machine_id)->where('tag_id', $condition['tag_id'])->where('offset', $condition['offset'])->first();
            }

            $condition['tag_name'] = $tag->name;
            $condition['device_name'] = $device->name;
            $condition['option'] = $tag->name . " " . $this->getMathExpressionFromString($condition['operator']). " " . $condition['value'];
        }

        return response()->json(compact('conditions'));

    }

    public function getApproachingThresholds(Request $request) {
        $user = $request->user('api');

        if ($user->hasRole(['customer_admin'])) {
            $conditions = Threshold::where('approaching_status', true)->get();
        } else {
            $conditions = Threshold::where('user_id', $user->id)->where('approaching_status', true)->get();
        }

        foreach ($conditions as $key => $condition) {
            $device = Device::where('device_id', $condition['device_id'])->first();

            $tag = MachineTag::where('configuration_id', $device->machine_id)->where('tag_id', $condition['tag_id'])->where('offset', $condition['offset'])->first();

            if (!$tag) {
                $tag = AlarmType::where('machine_id', $device->machine_id)->where('tag_id', $condition['tag_id'])->where('offset', $condition['offset'])->first();
            }

            $condition['tag_name'] = $tag->name;
            $condition['device_name'] = $device->name;
            $condition['option'] = $tag->name . " " . $this->getMathExpressionFromString($condition['operator']). " " . $condition['approaching'];
        }

        return response()->json(compact('conditions'));
    }

    public function clearApproachingStatus(Request $request) {
        $thresholds = $request->thresholds;

        foreach ($thresholds as $key => $threshold) {
            $row = Threshold::where('id', $threshold['id'])->first();

            $row->update([
                'approaching_status' => false
            ]);
        }

        return response()->json([
            'message' => 'Cleared thresholds successfully'
        ]);
    }

    public function clearThresholdStatus(Request $request) {
        $thresholds = $request->thresholds;

        foreach ($thresholds as $key => $threshold) {
            $row = Threshold::where('id', $threshold['id'])->first();

            $row->update([
                'message_status' => false
            ]);
        }

        return response()->json([
            'message' => 'Cleared thresholds successfully'
        ]);
    }

	public function getMathExpressionFromString($string) {
		if ($string == 'Equals') {
			return '=';
		} else if ($string == 'Does not equal') {
			return '!=';
		} else if ($string == 'Is greater than') {
			return '>';
		} else if ($string == 'Is greater than or equal to') {
			return '>=';
		} else if ($string == 'Is less than') {
			return '<';
		} else if ($string == 'Is less than or equal to') {
			return '<=';
		} else if ($string == 'Is in') {
			return 'in';
		} else if ($string == 'Is not in') {
			return 'not in';
		} else return '';
	}
}
