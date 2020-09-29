<?php

namespace App\Http\Controllers;

use App\Helpers\UserState;
use App\Notifications\Wechat;
use App\User;
use App\Apply;
use App\Group;
use App\WxTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApplyController extends Controller
{
    /**
     * 查询申请者列表
     * @param Request $request
     * @return JsonResponse
     */
    public function getApplicantList(Request $request)
    {
        $groupId = User::current()->group_id;
        $pageSize = $request->get('page_size', 15);
        $apply_ids = Apply::where('apply_team_id', $groupId)->get('apply_id');

        $applyUsers = User::whereIn('id', $apply_ids)->paginate($pageSize);
        return StandardSuccessJsonResponse($applyUsers);
    }

    /**
     * 查询申请者数量
     * @return JsonResponse
     */
    public function getApplicantCount()
    {
        $groupId = User::current()->group_id;
        $applyCount = Apply::where('apply_team_id', $groupId)->get();
        return StandardSuccessJsonResponse($applyCount);
    }

    /**
     * 申请入队
     * @param Request $request
     * @return JsonResponse
     */
    public function doApply(Request $request)
    {
        //todo Fix that
        $group = Group::current();
        if (!$group)
            return StandardFailJsonResponse('该队伍已经解散');
        if ($group->members >= $group->capacity)
            return StandardFailJsonResponse('该队伍已经满员');
        if ($group->is_submit)
            return StandardFailJsonResponse('该队伍已经锁定');

        $user = User::current();

        if ($group->captain_id == $user->id)
            return StandardFailJsonResponse('这是你自己的队伍');
        if ($user->group_id !== null)
            return StandardFailJsonResponse('你已经拥有队伍，无法申请');

        DB::transaction(function () use ($user, $group) {
            $user->state = UserState::appling;
            $user->save();
            Apply::create(['apply_team_id' => $group->id, 'apply_id' => $user->id]);
        });

        User::where('id', $group->captain_id)->first()->notify(new Wechat(WxTemplate::Apply));
        return StandardSuccessJsonResponse();
    }

    /**
     * 撤回申请
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteApply(Request $request)
    {
        $user = User::current();

        if ($user->state !== UserState::appling)
            return StandardFailJsonResponse('你的申请已经处理');

        $apply = Apply::where('apply_id', $user->id)->first();

        $apply->delete();
        return StandardSuccessJsonResponse();
    }

    /**
     * 同意加入
     * @param Request $request
     * @return JsonResponse
     */
    public function agreeMember(Request $request)
    {
        $user = User::current();
        $group = $user->group();

        $apply_id = $request->get('apply_id');

        if (!$group || $user->id !== $group->captain_id)
            return StandardFailJsonResponse('你没有权限处理申请');
        if ($group->members >= $group->capacity)
            return StandardFailJsonResponse('队伍已经达到上限');
        $applyUser = User::where('id', $apply_id)->first();
        if ($applyUser->state !== UserState::appling)
            return StandardFailJsonResponse('该申请者已经撤回申请了');
        $apply = Apply::where('apply_id', $apply_id);
        if ($apply === null)
            return StandardFailJsonResponse('申请不存在');

        DB::transaction(function () use ($group, $apply, $applyUser) {
            $applyUser->group_id = $group->id;
            $applyUser->state = UserState::member;
            $applyUser->save();
            $apply->delete();
            $applyUser->notify(new Wechat(WxTemplate::Agree));
        });

        return StandardSuccessJsonResponse();
    }

    /**
     * 拒绝加入
     * @param Request $request
     * @return JsonResponse
     */
    public function refuseMember(Request $request)
    {
        $user = User::current();
        $group = $user->group();

        if ($user->id !== $group->captain_id)
            return StandardSuccessJsonResponse('你没有权限处理申请');

        $apply_id = $request->get('apply_id');
        $applyUser = User::where('id', $apply_id)->first();

        if ($applyUser->state !== UserState::appling)
            return StandardSuccessJsonResponse('该申请者已经撤回申请了');

        $apply = Apply::where('apply_id', $apply_id);

        if ($apply === null)
            return StandardFailJsonResponse('申请不存在');


        DB::transaction(function () use ($apply, $applyUser) {
            $apply->delete();
            $applyUser->state = UserState::no_entered;
            $applyUser->save();
            $applyUser->notify(new Wechat(WxTemplate::Refuse));
        });
        return StandardSuccessJsonResponse();


    }

}