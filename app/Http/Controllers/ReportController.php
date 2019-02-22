<?php

namespace App\Http\Controllers;

use App\Group;
use App\Knowledge;
use App\Member;
use App\Nation;
use App\Political;
use App\Position;
use App\Religion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(){
        $user = Auth::user();
        if ($user->isAn('admin')){
            $groups = Group::all();
        } else{
            $group = $user->group;
            $ids = $group->getIdsG();
            $groups = Group::whereIn('id', $ids)->get();
        }

        $positions = Position::all();
        $knowledges = Knowledge::all();
        $politicals = Political::all();
        $nations = Nation::all();
        $religions = Religion::all();
        return view('report.index',compact('groups','positions','knowledges','politicals','nations','religions'));
    }

    private function getData($request){
        $group_id = $request->get("child_group_id");
        $position = $request->get("position");
        $term = $request->get("term");
        $gender = $request->get("gender");
        $birthday_from = $request->get("birthday_from");
        $birthday_to = $request->get("birthday_to");
        $join_date_from = $request->get("join_date_from");
        $join_date_to = $request->get("join_date_to");
        $knowledge = $request->get("knowledge");
        $political = $request->get("political");
        $current_district = $request->get("current_district");
        $nation = $request->get("nation");
        $religion = $request->get("religion");
        $relation = $request->get("relation");

        $query = Member::select(DB::raw('members.fullname as fullname,members.gender as gender, members.birthday as birthday, 
                                                members.nation_text as nation, members.position_text as position,
                                                members.knowledge_text as knowledge, members.political_text as political,
                                                members.religion_text as religion,
                                                groups.name as group_name,members.group_id as group_id,members.education_level as education_level,
                                                groups.parent_id as parent_id,groups.level as level'))
            ->leftJoin('groups','members.group_id','=','groups.id');
        if($group_id){
            $group = Group::where('id',$group_id)->first();
            $ids = $group->getIdsG();
            $query->whereIn('members.group_id',$ids);
        } else{
            $ids = '';
        }
        if($position){
            $query->where('members.position','=',$position);
        }
        if($term){
            $query->where('members.term','like','"%'.$term.'%"');
        }
        if($gender){
            $query->where('members.gender','=',$gender);
        }
        if($birthday_from){
            $query->where('members.birthday','>=',$birthday_from);
        }
        if($birthday_to){
            $query->where('members.birthday','<=',$birthday_to);
        }
        if($join_date_from){
            $query->where('members.join_date','>=',$join_date_from);
        }
        if($join_date_to){
            $query->where('members.join_date','<=',$join_date_to);
        }
        if($knowledge){
            $query->where('members.knowledge','=',$knowledge);
        }
        if($political){
            $query->where('members.political','=',$political);
        }
        if($current_district){
            $query->where('members.current_district','like','"%'.$current_district.'%"');
        }
        if($nation){
            $query->where('members.nation','=',$nation);
        }
        if($religion){
            $query->where('members.religion','=',$religion);
        }
        if($relation){
            $query->where('members.relation','=',$relation);
        }
        $members = $query->orderBy('parent_id','DESC')->orderBy('level','ASC')->get();
//        $data = $this->groupData($members);
        $data = [
            'members'=>$members,
            'group_ids'=>$ids
        ];
        return $data;
    }

    private function groupData($members){
        $result = array();
        foreach ($members as $member){
            if(array_key_exists($member->parent_id,$result)){
                if(array_key_exists($member->group_id,$result[$member->parent_id])){
                    $result[$member->parent_id][$member->group_id][]= $member->toArray();
                } else{
                    $result[$member->parent_id][$member->group_id] = [$member->toArray()];
                }
            } else{
                $result[$member->parent_id] = [$member->group_id => [$member->toArray()]];
            }
        }
        return $result;
    }
    public function exportToWord(Request $request)
    {
        $data = $this->getData($request);
        $result = $data['members'];
        $ids = $data['group_ids'];
        $report_name = $request->get("report_name");
        $group_name = $request->get("group_name");
        return view('export.word',compact('result','ids','report_name','group_name'));

    }

    public function exportToExcel(Request $request){
        $data = $this->getData($request);
        $result = $data['members'];
        $ids = $data['group_ids'];
        $report_name = $request->get("report_name");
        $group_name = $request->get("group_name");
        return view('export.excel',compact('result','ids','report_name','group_name'));
    }
}
