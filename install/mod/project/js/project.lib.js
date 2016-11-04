/**
 * Created by zoran on 04/11/16.
 */

function changeProjectGroup(courseid, projectid, groupid, disabled){
    if(disabled===true){
        console.log("Disable");
    }else{
        console.log("Enable");
    }
    console.log("GROUP ID:"+groupid);
    if(document.getElementById("group_"+groupid).checked){
        console.log("ELEMENT CHECKED");
    }else{
        console.log("ELEMENT UNCHECKED");
    }
    $.ajax({
        type: "POST",
        url: M.cfg.wwwroot+"/mod/project/ajaxlib.php",
        data: {courseid:courseid, projectid:projectid, groupid: groupid, disabled: disabled, action:'projectgroupactivate'},
        dataType: "json",
        success: function(data){
            console.log("SUCCESS IN ACTIVATION");
            console.log("PROCESSING DATA 2:"+JSON.stringify(data));


        },
        error: function(err){
            console.log("ERROR:"+JSON.stringify(err));
            // start_notifications_poll();
        }
    });
}
