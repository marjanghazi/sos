<script>
function uploadFile() {
        if (document.getElementById("att-name").value == "") {
            document.getElementById("fnerr").innerHTML = 'Name Required!';
            document.getElementById("flerr").innerHTML = '';
        }
        else if (document.getElementById("fileupload").files.length == 0) {
            document.getElementById("flerr").innerHTML = 'No File Selected';
            document.getElementById("fnerr").innerHTML = '';
        }
        else{
        document.getElementById("loadgif").style.opacity = '1';
        document.getElementById("fnerr").innerHTML = '';
        document.getElementById("flerr").innerHTML = '';
        var pid = document.getElementById("pid_ajax").value;
        var nm = document.getElementById("att-name").value;
        let formData = new FormData();
        formData.append("file", fileupload.files[0]);
        formData.append("pid", pid);
        formData.append("nm", nm);
        let xml = new XMLHttpRequest();
        var url = "upload.php";
        xml.open("POST",url);
        xml.send(formData);
        xml.onload = () => document.getElementById("attdiv").innerHTML =xml.response;
        document.getElementById("fileupload").value = "";
        document.getElementById("att-name").value = "";
        }
    }
    function deletefile(argument) {
            let result = confirm("Are you sure?");
            if (result === true) {
            var pid = document.getElementById("pid_ajax").value;
            xml = new XMLHttpRequest();
            var url = "attreload.php?did="+argument+"&pid="+pid;
            xml.open("GET",url,"true");
            xml.send();
            xml.onreadystatechange = function(){
                if (xml.readystate = 4) {
                    document.getElementById("attdiv").innerHTML = xml.responseText;
            }
            return false;
        }
        }
    }
</script>