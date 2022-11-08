<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Validator;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try{
            $query = Task::whereNull('task_id')->with('sub_task');


            if ($request->search != null) {
                $query->where('title', 'like', '%' . $request->get('search') . '%');
            }

            if ($request->status == 'pending') {
                $query->where('record_status', 'P');
                $query->with(['sub_task' => function ($q) {
                    $q->where('record_status', 'P');
                }]);
            }

            if ($request->filter == 'today') {
                $query->where('due_date', Carbon::today()->format('Y-m-d'));
            }

            if ($request->filter == 'current_week') {

                $now = Carbon::now();
                $weekStartDate = $now->startOfWeek()->format('Y-m-d');
                $weekEndDate = $now->endOfWeek()->format('Y-m-d');
                $query->where('due_date', '>=',$weekStartDate);
                $query->where('due_date', '<=', $weekEndDate);

                $query->where('record_status', 'P');
                $query->with(['sub_task' => function ($q) {
                    $q->where('record_status', 'P');
                }]);
            }

            if ($request->filter == 'next_week') {
                $now = Carbon::now();
                $weekStartDate = $now->startOfWeek()->addDays(7)->format('Y-m-d');
                $weekEndDate = $now->endOfWeek()->addDays(7)->format('Y-m-d');
                $query->where('due_date', '>=',$weekStartDate);
                $query->where('due_date', '<=', $weekEndDate);

                $query->where('record_status', 'P');
                $query->with(['sub_task' => function ($q) {
                    $q->where('record_status', 'P');
                }]);
            }
            if ($request->filter == 'overdue') {
                $now = Carbon::now()->format('Y-m-d');
                $query->where('due_date', '<', $now);

                $query->where('record_status', 'P');
                $query->with(['sub_task' => function ($q) {
                    $q->where('record_status', 'P');
                }]);
            }

            $query->orderBy('due_date', 'ASC');

            $data = $query->get();

            $output = [
                'status' => 200,
                'message' => 'success',
                'data' => $data
            ];
        }catch (\Exception $e){
            Log::error($e);
            $output = [
                'status' => 400,
                'message' => 'Try again later',
                'data' => []
            ];
        }

        return response()->json($output);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|regex:/^[ a-zA-Z0-9_[\]()\-]+$/u',
            'due_date' => 'required|date:Y-m-d',
            'description' => 'required|regex:/^[ a-zA-Z0-9_[\]()\-]+$/u',
            'task_id' => 'sometimes|numeric',
        ]);
        if ($validator->fails()) {
            $output = [
                'status' => 401,
                'message' => 'validation errors',
                'data' => $validator->errors()
            ];
            return response()->json($output);
        }

        try {
            $task = new Task($request->all());
            $task->save();
            $output = [
                'status' => 200,
                'message' => 'Success',
                'data' => $task
            ];
        }catch (\Exception $e) {
            Log::error($e);
            $output = [
                'status' => 400,
                'message' => 'Try again later',
                'data' => []
            ];
        }
        return response()->json($output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $task = Task::find($id);
        if ($task == null) {
            $output = [
                'status' => 404,
                'message' => 'Record Not Found',
                'data' => []
            ];
            return response()->json($output);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $record = Task::find($id);
        if ($record) {
            try{

                Task::destroy($id);
                $output = [
                    'status' => 200,
                    'message' => 'Success',
                    'data' => []
                ];
            }catch (\Exception $e){
                Log::error($e);
                $output = [
                    'status' => 400,
                    'message' => 'Try again later',
                    'data' => []
                ];
            }

        }else{
            $output = [
                'status' => 404,
                'message' => 'Record Not Found',
                'data' => []
            ];
        }

        return response()->json($output);
    }


    public function update_status(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['C', 'P'])],
        ]);

        if ($validator->fails()) {
            $output = [
                'status' => 401,
                'message' => 'validation errors',
                'data' => $validator->errors()
            ];
            return response()->json($output);
        }

        $task = Task::find($id);
        if ($task == null) {
            $output = [
                'status' => 404,
                'message' => 'Record Not Found',
                'data' => []
            ];
            return response()->json($output);
        }

        DB::transaction(function () use ($request, $task) {
            $task->record_status = $request->status;
            $task->save();
        });

    }
}
