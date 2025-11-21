<?php
include 'assets/include/dbconnect.php';
                                        $result = mysqli_query($conn,"SELECT * from att where invno = '$sp_id' and active='0'") or die(mysqli_error($conn));
                                        echo "<table class='table-bordered' style='width:95%;margin:auto    '>";
                                        echo "<tr>";
                                        echo "<th>Sr.</th>";
                                        echo "<th>Name</th>";
                                        echo "<th>FileName</th>";
                                        echo "<th>Action</th>";
                                        echo "</tr>";
                                        $n=1;
                                        while ($row = mysqli_fetch_array($result)) {
                                            echo "<tr>";
                                            echo "<td>$n</td>";
                                            echo "<td>".$row['fname']."</td>";
                                            echo "<td>".$row['location']."</td>";
                                            echo "<td><a  href='attachments/".$row['location']."' target='_blank'><i class='fas fa-eye text-success' title='View'></i></a> | <a  href='attachments/".$row['location']."' download><i class='fas fa-download'></i></a> | <i class='fas fa-trash text-danger' style='cursor:pointer;' onclick='deletefile(".$row['id_pk'].")'></i></td>";
                                            echo "</tr>";
                                            $n++;
                                        }
                                        echo "</table";
                                    ?>
                                    