<?php

namespace App\Http\Controllers;

use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\APIResource; // Ensure you have this resource or a similar way to format responses

class TemplateController extends Controller
{
    public function index()
    {
        try {
            $templates = Template::all();
            return new APIResource($templates, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'value' => 'required|string',
            ]);

            $template = Template::create($request->all());

            return new APIResource($template, false, 201);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function show(Template $template)
    {
        try {
            return new APIResource($template, false, 200);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'id' =>'required',
                'name' => 'string|max:255',
                'value' => 'string',
            ]);
            $data = $request->all();
            unset($data['id']);
            $template = Template::where('id',$request->get('id'))->update($data);

            return new APIResource($template, false, 200);
        } catch (ValidationException $e) {
            return new APIResource(array_values($e->errors())[0], true, 400);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }

    public function destroy(Template $template)
    {
        try {
            $template->delete();
            return new APIResource(null, false, 204);
        } catch (\Exception $e) {
            return new APIResource($e->getMessage(), true, 400);
        }
    }
}
