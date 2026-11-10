@csrf
@if(isset($announcement))@method('PUT')@endif
@foreach($errors->all() as $error)<p class="text-red-600">{{$error}}</p>@endforeach
<label class="block mb-3">Title<input required name="title" class="border p-2 block w-full" value="{{old('title',$announcement->title??'')}}"></label>
<label class="block mb-3">Content<textarea required name="content" rows="7" class="border p-2 block w-full">{{old('content',$announcement->content??'')}}</textarea></label>
<label class="block mb-3">Image<input type="file" accept="image/*" name="image"></label>
@foreach(['type'=>['general','promotion','event','maintenance','important'],'target_audience'=>['all','customers','staff'],'status'=>['draft','scheduled','published','archived']] as $field=>$options)<label class="block mb-3">{{ucwords(str_replace('_',' ',$field))}}<select class="border p-2 block w-full" name="{{$field}}">@foreach($options as $option)<option value="{{$option}}" @selected(old($field,$announcement->$field??$options[0])===$option)>{{$option}}</option>@endforeach</select></label>@endforeach
<label class="block mb-3"><input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned',$announcement->is_pinned??false))> Pin announcement</label>
<label class="block mb-3">Publish at<input type="datetime-local" name="published_at" value="{{old('published_at',isset($announcement)&&$announcement->published_at?$announcement->published_at->format('Y-m-d\TH:i'):'')}}" class="border p-2 block"></label>
<label class="block mb-3">Expires at<input type="datetime-local" name="expires_at" value="{{old('expires_at',isset($announcement)&&$announcement->expires_at?$announcement->expires_at->format('Y-m-d\TH:i'):'')}}" class="border p-2 block"></label>
<button class="bg-stone-900 text-white px-6 py-3 rounded">Save announcement</button>