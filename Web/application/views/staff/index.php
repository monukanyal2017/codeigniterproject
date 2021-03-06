
    <!-- page content -->
    <div class="right_col" role="main">
        <div class="">
            <div class="page-title">
                <div class="title_left">
                    <h3>Staff Management <small>Listing</small></h3>
                </div>

                <div class="title_right">
                    <div class="col-md-5 col-sm-5 col-xs-12 form-group pull-right top_search">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Search for...">
                            <span class="input-group-btn">
                                <button class="btn btn-default" type="button">Go!</button>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
                                        <!-- new row 29march -->
                                        <?php 
                            if(!empty($staff_data))
                            {
                                // echo "<pre>";
                                // print_r($staff_data);
                                ?>
                            <div class="row">
                             <div class="col-md-12 col-sm-12 col-xs-12 ">
                                <center><h3 style="color: black">Seconday Admin Selection</h3></center>
                            </div>
                            </div>
                            <div class="row">
                            <div class="col-md-12 col-sm-12 col-xs-12 ">
                            
                                <table id="datatable" class="table table-striped table-bordered">
                                      <thead>
                                        <tr>
                                        <th>Current Secondary Admin</th>
                                          <th>Staff Name</th>
                                          <th>Action</th>

                                        </tr>
                                      </thead>
                                       <tbody>
                                   
                                       <form action="<?php echo site_url('Staff/update_seconday_admin');?>" method="post">
                                        <tr>
                                            <td>
                                           <?php
                                                for($j=0;$j<count($staff_data);$j++)
                                                {
                                                ?>
                                          <?php if($staff_data[$j]['is_secondary_admin']==1){ ?>
                                              <strong style="color: green"> <?php echo $staff_data[$j]['first_name']." ".$staff_data[$j]['last_name']; ?></strong>
                                          <?php } ?>
                                          <?php 
                                            }
                                            ?>

                                          </td>
                                          <td><div class="form-group">
                                              <label for="sel1">Select list:</label>
                                              <select class="form-control" name="staff_id" required>
                                                 <?php
                                                for($i=0;$i<count($staff_data);$i++)
                                                {
                                                ?>
                                                <option value="<?php echo $staff_data[$i]['id']; ?>"><?php echo $staff_data[$i]['first_name']." ".$staff_data[$i]['last_name']; ?></option>
                                                <?php
                                                    }
                                                    ?>
                                              </select>
                                            </div>
                                            </td>
                                             <td>
                                             <input type="submit" name="update" id="update<?php echo $i; ?>" class="btn btn-success " value="update ">
                                          </td>

                                        </tr>
                                    </form>
                               
                                
                              </tbody>
                            </table>
                           
                            </div>
                            </div>
                             <?php

                            }
                            ?>
                            <!-- end new row -->
            <div class="row">

                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="x_panel">
                        <div class="x_title">
                            <h2>Daily active staffs <small>Grid List</small></h2>
                            <ul class="nav navbar-right panel_toolbox">
                                <li><button onClick="window.open('<?php echo site_url().'/staff/add'; ?>','_self');" class="btn btn-info btn-xs">Add New</button></li>
                                
                            </ul>
                            <div class="clearfix"></div>
                        </div>
                        <div class="x_content">

                        <div class="form-group">
                        <?php if($this->session->flashdata('flash_message')){
                            echo "<div class='flash success'>".$this->session->flashdata('flash_message')."</div>";
                        } ?>
                        <?php if($this->session->flashdata('flash_error')){
                            echo "<div class='flash error'>".$this->session->flashdata('flash_error')."</div>";
                        } ?>
                        </div>
                            <div class="row">

                            <?php
                            if (!empty($arrStaff))
                            {
                              foreach ($arrStaff as $key=>$row)
                              { ?>
                            <?php 
                            $address = "";
                            $address.=($row['address']!="") ? $row['address'] : '';
                            $address.=($row['city']!="") ? ", ".$row['city'] : '';
                            $address.=($row['state']!="") ? ", ".$row['state'] : '';
                            $address.=($row['pincode']!="") ? " - ".$row['pincode'] : '';

                            ?>                          
                                <div class="col-md-4 col-sm-4 col-xs-12 animated fadeInDown">
                                    <div class="well profile_view">
                                        <div class="col-sm-12">
                                            <div class="left col-xs-7">
                                                <h2><?php echo $row['first_name']." ".$row['middle_name']." ".$row['last_name']; ?></h2>

                                                <p><strong>Gender: </strong> <?php echo $row['gender']; ?> </p>
                                                <?php if($row['about'] != "") { ?>
                                                <p><strong>About: </strong> <?php echo substr($row['about'],0,100).'...'; ?> </p>
                                                <?php } ?>
                                                
                                            </div>
                                            <div class="right col-xs-5 text-center">
                                            <?php if($row['image'] != "")
                                                    $email_src = base_url().$this->config->item('upload_staff_abs').$this->session->userdata['logged_in']['admin_id']."/".$row['image'];
                                                else
                                                    $email_src = base_url('images/img/user.png');
                                            ?>
                                                <img src="<?php echo $email_src ?>" alt="" class="img-circle img-responsive">
                                            </div>
                                            <div class="col-xs-12">
                                                <ul class="list-unstyled">
                                                    <li><i class="fa fa-home"></i> <?php echo $address; ?></li>
                                                    <li><i class="fa fa-envelope"></i> <?php echo $row['email']; ?></li>
                                                    <li><i class="fa fa-phone"></i> <?php echo $row['mobile']; ?> </li>

                                                </ul>
                                            </div>
                                        </div>
                                        <div class="col-xs-12 bottom text-center">
                                            <div class="col-xs-12 col-sm-5 emphasis">
                                                <?php if($row['is_active'] == true) {
                                                    echo '<strong>Active </strong><div class="label label-success">✓</div>';
                                                } else {
                                                    echo '<strong>InActive </strong><div class="label label-danger">✘</div>';
                                                } ?>
                                               
                                            </div>
                                            <div class="col-xs-12 col-sm-7 emphasis">
<a href="<?php echo site_url('staff/show/'.$row['id']); ?>" class="btn btn-success btn-xs" title="Show"><i class="fa fa-info-circle"></i></a>
<a href="<?php echo site_url('staff/edit/'.$row['id']); ?>" type="button" class="btn btn-info btn-xs" title="Edit"> <i class="fa fa-user"></i> Edit Profile </a>
<a href="<?php echo site_url('staff/delete/'.$row['id']); ?>" class="btn btn-danger btn-xs" title="Delete" onclick="return confirm('Do you want to permanent delete this staff member?')"><i class="fa fa-trash"></i></a>
                                            </div>
                                        </div>
                                    </div> <!-- well profile_view -->
                                </div>

                            <?php } 
                            }
                            else
                            { 
                                echo '<div class="col-md-12"><div class="alert alert-danger alert-dismissible fade in no-data">No record found!!</div></div>';
                            } ?>
                            </div> <!-- row -->

                        </div>
                    </div>
                </div>

                <br />
                <br />
                <br />

            </div>
        </div>
            <!-- footer content -->