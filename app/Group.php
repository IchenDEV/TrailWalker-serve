<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{


    protected $fillable = [
        'name', 'capacity', 'description', 'route', 'captain_id'
    ];

    /**
     *  获取所有组员
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function members() {
        return $this->hasMany('App\User');
    }

    /**
     * 追加字段
     * @var array
     */
    protected $appends = ['members' ];

    /**
     * 获取队员
     */
    public function getMembersAttribute() {
        return $this->members()->count();
    }


    /*
     * 获取队伍数量
     */
    static public function getTeamCount() {
        return Group::count();
    }


    /**
     * 删除队伍
     * @return bool|null
     * @throws \Exception
     */
    public function delete()
    {
        $members = $this->members()->get();
        $data = [
            'first' => "你的队伍已经被解散",
            'keyword1' => '队伍解散',
            'keyword2' => '解散成功',
            'keyword3' => date('Y-m-d H:i:s', time()),
            'remark'   => '还想加入队伍，请点击详情'
        ];
        $data_2 = [
            'first' => "你申请的队伍已经被解散",
            'keyword1' => '队伍解散',
            'keyword2' => '解散成功',
            'keyword3' => date('Y-m-d H:i:s', time()),
            'remark'   => '还想加入队伍，请点击详情'
        ];
        $applys = YxApply::where('apply_team_id', $this->id)->get();
        foreach ($applys as $apply) {
            $user = User::where('id', $apply->apply_id)->first();
            $user->notify($data_2);
            $userState = $user->state()->first();
            $userState->state = 1;
            $userState->save();
            $apply->delete();
        }
        foreach ($members as $member) {
            $member->yx_group_id = null;
            $memberState = $member->state()->first();
            $memberState->state = 1;
            $member->notify($data);
            $member->save();
            $memberState->save();
        }
        return parent::delete(); // TODO: Change the autogenerated stub
    }


    /**
     * 通知队长
     */
    public function notifyCaptain() {
        $data = [
            'first' => "有人申请加入你的队伍",
            'keyword1' => '队伍申请',
            'keyword2' => '正在申请',
            'keyword3' => date('Y-m-d H:i:s', time()),
            'remark'   => '点击详情，进入我的队伍查看申请信息'
        ];
        $user = User::where('id', $this->captain_id)->first();
        $user->notify($data);
    }


}
