<!-- Topbar -->
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow" style="background-image: linear-gradient(to left, #09203f 0%, #537895 100%);">

                    <!-- Sidebar Toggle (Topbar) -->
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                        <i class="fa fa-bars"></i>
                    </button>
                    <!-- Topbar Navbar -->
                    <ul class="navbar-nav ml-auto">

                        <!-- Nav Item - Search Dropdown (Visible Only XS) -->
                        <li class="nav-item dropdown no-arrow d-sm-none">
                            <a class="nav-link dropdown-toggle" href="#" id="searchDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fas fa-search fa-fw"></i>
                            </a>
                            <!-- Dropdown - Messages -->
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow animated--grow-in"
                                aria-labelledby="searchDropdown">
                                <form class="form-inline mr-auto w-100 navbar-search">
                                    <div class="input-group">
                                        <input type="text" class="form-control bg-light border-0 small"
                                            placeholder="Search for..." aria-label="Search"
                                            aria-describedby="basic-addon2">
                                        <div class="input-group-append">
                                            <button class="btn btn-primary" type="button">
                                                <i class="fas fa-search fa-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </li>

                        <div class="topbar-divider d-none d-sm-block"></div>

                        <!-- Nav Item - User Information -->
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <strong class="mr-2 d-none d-lg-inline"><?php echo $_SESSION['name']; ?></strong>
                                <img class="img-profile rounded-circle"
                                    src="img/undraw_profile.svg">
                            </a>
                            <!-- Dropdown - User Information -->
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#passModal">
                                    <i class="fas fa-list fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Change Password
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Logout
                                </a>
                            </div>
                        </li>

                    </ul>

                </nav>
                <!-- End of Topbar -->
                <!-- Password Change Modal-->
    <div class="modal fade" id="passModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Password Change!</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="change_res" class="text-center"></div>
                    <label>Old Password</label>
                    <input type="password" id="oldpassword" class="form-control" >
                    <label>New Password</label>
                    <input type="password" id="newpassword" class="form-control" onkeyup="validatei()">
                    <label>Confirm Password</label>
                    <input type="password" id="confirmpassword" class="form-control" onkeyup="validateb()">
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-primary" href="#" onclick="passw(document.getElementById('oldpassword').value,document.getElementById('newpassword').value);" >Change Password</a>
                </div>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        function passw(arg1,arg2) {
            var len = document.getElementById('newpassword').value;
            if (document.getElementById('oldpassword').value == ""){
                document.getElementById('oldpassword').style.border = "3px solid pink";
            }else if(document.getElementById('newpassword').value == ""){
                document.getElementById('newpassword').style.border = "3px solid pink";
            }else if(document.getElementById('confirmpassword').value == "") {
                document.getElementById('confirmpassword').style.border = "3px solid pink";
            }
            else if(len.length<8){
                document.getElementById('newpassword').style.border = "3px solid pink";
                document.getElementById("change_res").innerHTML = "New password length is less than 8 characters!";

            }else if(document.getElementById('newpassword').value != document.getElementById('confirmpassword').value){
                document.getElementById('confirmpassword').style.border = "3px solid pink";
                document.getElementById("change_res").innerHTML = "Both passwords don't match!";
            }else{
            var xmlhttp = new XMLHttpRequest();
            var url = "assets/include/ajax.php?changepassword="+arg1+"&newp="+arg2;
            xmlhttp.open("GET",url,true);
            xmlhttp.send();
            xmlhttp.onreadystatechange = function(){
                if (xmlhttp.readystate = 4 && xmlhttp.status == 200) {
                    document.getElementById("change_res").innerHTML = xmlhttp.responseText;
                    document.getElementById('oldpassword').value = "";
                    document.getElementById('newpassword').value = "";
                    document.getElementById('confirmpassword').value = "";
                    document.getElementById('confirmpassword').style.border = "1px solid #d1d3e2";
                    document.getElementById('newpassword').style.border = "1px solid #d1d3e2";
                }
            }
        }
    }
    function validatei() {
    if (document.getElementById('newpassword').value == "") {
         document.getElementById('newpassword').style.border = "1px solid #d1d3e2";
    }
    else{
        var len = document.getElementById('newpassword').value;
        if(len.length<8){
            document.getElementById('newpassword').style.border = "3px solid pink";
        }
        else{
            document.getElementById('newpassword').style.border = "3px solid green";
        }
    }
    }
    function validateb() {
        if (document.getElementById('confirmpassword').value == "") {
         document.getElementById('confirmpassword').style.border = "1px solid #d1d3e2";
    }
    else{
        var len = document.getElementById('newpassword').value;
        if(document.getElementById('newpassword').value == document.getElementById('confirmpassword').value && len.length>7){
            document.getElementById('confirmpassword').style.border = "3px solid green";
            
        }
        else{
            document.getElementById('confirmpassword').style.border = "3px solid pink";
        }
    }
}
    </script>