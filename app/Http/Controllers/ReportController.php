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
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
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

    private function getData($request, $type){
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

        $query = Member::select(DB::raw('members.id as id, members.fullname as fullname,members.gender as gender, members.birthday as birthday, 
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

        $report_name = $request->get("report_name");
        $group_name = $request->get("group_name");
        $fileList = [];

        if($type == 1){
            $fileType = '.xls';
            $path = public_path('export/excel/');
        } else{
            $fileType = '.doc';
            $path = public_path('export/word/');
        }
        $members = $query->orderBy('parent_id','DESC')->orderBy('level','ASC')->get()->toArray();


        $members = array_chunk($members,1000);
        $page = count($members);;
        $n = 0;
        foreach ($members as $memberList){
            $data = $this->groupData($memberList);
            if($type == 1){
                $view = View::make('export.excel', ['result' => $data,'report_name'=>$report_name,'group_name'=>$group_name,'i'=>$n*1000]);
            } else{
                $view = View::make('export.word', ['result' => $data,'report_name'=>$report_name,'group_name'=>$group_name,'i'=>$n*1000]);
            }
            $contents = $view->render();
            $fileName = $report_name;
            if($n == $page - 1 ){
                $fileName .= ' - '.($n + 1).' - end';
            } else{
                $fileName .= ' - '.($n + 1);
            }
            $fileName .=$fileType;
            $myfile = fopen($path.$fileName, "w");
            fwrite($myfile, $contents);
            $fileList[] = $fileName;
            $n++;
        }
        return $fileList;
    }

    private function groupData($members){
        $result = array();
        foreach ($members as $member){
            if(array_key_exists($member['parent_id'],$result)){
                if(array_key_exists($member['group_id'],$result[$member['parent_id']])){
                    $result[$member['parent_id']][$member['group_id']][]= $member;
                } else{
                    $result[$member['parent_id']][$member['group_id']] = [$member];
                }
            } else{
                $result[$member['parent_id']] = [$member['group_id'] => [$member]];
            }
        }
        return $result;
    }
    public function exportToWord(Request $request)
    {
        $fileList = $this->getData($request,0);
        return json_encode($fileList);
    }

    public function exportToExcel(Request $request){
        $fileList = $this->getData($request,1);
        return json_encode($fileList);
    }

    public function deleteDownloadedFile(Request $request){
        $fileList = $request->get('filelist');
        $type = $request->get('type');
        foreach ($fileList as $file){
            try{
                unlink(public_path('export/'.$type.'/'.$file));
            } catch (\Exception $exception){}
        }
    }

    public function preview(Request $request){
        $data = $this->processData($request);
        return $data;
    }

    private function processData($request){
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
        $start = $request->get('start');
        $query = Member::select(DB::raw('members.id as id, members.fullname as fullname,members.gender as gender, members.birthday as birthday, 
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
        $total = $query->orderBy('parent_id','DESC')->orderBy('level','ASC')->count();
        $page = ceil($total/1000);
        $members = $query->orderBy('parent_id','DESC')->orderBy('level','ASC')->skip(($start-1)*1000)->take(1000)->get()->toArray();
        $report_name = $request->get("report_name");
        $group_name = $request->get("group_name");
        $data = $this->groupData($members);
        $contents = $header = $footer = '';
        if($start == 1){
            $view = View::make('export.header',['report_name'=>$report_name,'group_name'=>$group_name]);
            $header .= $view->render();
        }
        $n = (($start - 1 ) >0 )?($start - 1):0;
        $view = View::make('export.preview', ['result' => $data,'report_name'=>$report_name,'group_name'=>$group_name,'i'=>$n*1000]);
        $contents .= $view->render();
        if($start + 1 >= $page){
            $view = View::make('export.footer',['i'=>$total]);
            $footer .= $view->render();
        }
        $currentPage = ($start>1)?$start:1;
        $start += 1;
        $result = [
            'contents'=>$contents,
            'start'=>$start,
            'footer'=>$footer,
            'header'=>$header,
            'page'=>$this->generatePagination($page,$currentPage)
        ];
        return $result;
    }

    private function generatePagination($total, $currentPage){
        $showAll = false;
        $end_size = 3;
        $mid_size = 3;
        $dots = false;
        $html ='<ul class="pagination" role="navigation">';
        if($currentPage == 1){
            $html .=' <li class="page-item disabled" aria-disabled="true" aria-label="previous">
                <span class="page-link" aria-hidden="true" data-page="...">&lsaquo;</span>
            </li>';
        } else{
            $html .='<li class="page-item">
                <a class="page-link" href="#" rel="prev" aria-label="previous" data-page="'.($currentPage - 1).'">&lsaquo;</a>
            </li>';
        }
        for ($n=1; $n <= $total;$n++){
            if($n == $currentPage){
                $html .='<li class="page-item active" aria-current="page"><span class="page-link" data-page="'.$n.'">'.$n.'</span></li>';
                $dots = true;
            } else{
                if ( $showAll || ( $n <= $end_size || ( $currentPage && $n >= $currentPage - $mid_size && $n <= $currentPage + $mid_size ) || $n > $total - $end_size ) ) {
                    $html .=' <li class="page-item"><a class="page-link" href="#" data-page="'.$n.'">'.$n.'</a></li>';
                    $dots = true;
                } else {
                    if ( $dots && ! $showAll ) {
                        $html .='<li class="page-item disabled" aria-disabled="true"><span class="page-link" data-page="...">...</span></li>';
                        $dots = false;
                    }
                }
            }
        }
        if($currentPage < $total){
            $html .='<li class="page-item">
                <a class="page-link" href="" rel="next" aria-label="next" data-page="'.($currentPage + 1).'">&rsaquo;</a>
            </li>';
        } else{
            $html .='<li class="page-item disabled" aria-disabled="true" aria-label="next">
                <span class="page-link" aria-hidden="true" data-page="'.($currentPage + 1).'">&rsaquo;</span>
            </li>';
        }
        $html .='</ul>';
        return $html;
    }
}
