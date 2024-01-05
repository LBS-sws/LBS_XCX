<?php
date_default_timezone_set('Asia/Hong_Kong');
require './db.php';
require './email.php';
require './config.php';

$loginacc = isset($argv[1]) ? $argv[1] : '1';

echo date("Y-m-d H:i:s")." sendreport - Read mail queue ...\n";

$result = mysqli_query($db, "select * from mail_report_queue where status='P' order by id limit 450");
// $result = mysqli_query($db, "select * from mail_report_queue where id IN(486504,486505)");
while ($row = mysqli_fetch_array($result)) {

    echo date("Y-m-d H:i:s")." sendreport - Process mail ".$row['id']." ...\n";
    markStatus($db, $row['id'], 'I');

    $customer = $row['job_type']==2
        ? mysqli_query($db, "select b.NameZH, b.NameShop, b.Email,b.CustomerID,a.SType as service_type from lbs_xcx.followuporder a, lbs_xcx.customercompany b where a.CustomerID=b.CustomerID and a.FollowUpID=".$row['job_id'])
        : mysqli_query($db, "select b.NameZH, b.NameShop, b.Email,b.CustomerID,a.ServiceType as service_type from lbs_xcx.joborder a, lbs_xcx.customercompany b where a.CustomerID=b.CustomerID and a.JobID=".$row['job_id']);

    if ($cust = mysqli_fetch_array($customer)) {
        // 判断客户ID是不是在名单内
        if(in_array($cust['CustomerID'], ['XCWPN001'])){ //王品牛排（西单店）

            // 客户的服务类型是2 才执行发送邮件
            if ($cust['service_type'] == 2) {
                $dList = array();
                $qid = $row['id'];
                $rtn = '';

                if (empty($cust['Email'])) {
                    $rtn = '錯誤: 您必须提供至少一个收件人电子邮件地址';
                    echo date("Y-m-d H:i:s")." Error: You must provide at least one recipient email address.\n";
                } else {
                    $mail = new email();
                    $mail->senderAddress = 'no-reply@lbsgroup.com.cn';
                    $mail->senderName = 'LBS';
                    $mail->recipient = json_encode(explode(',',$cust['Email']));
                    $mail->ccaddress = '';
                    $mail->subject = '<'.$cust['NameZH'].'> - 史伟莎服务报告';
                    $mail->content = file_get_contents('./mail.html');
                    echo date("Y-m-d H:i:s")." TO: ".$mail->recipient." / SUBJ: ".$mail->subject."\n";

                    $data = array(
                        'staffid' => $loginId,
                        'job_id' => $row['job_id'],
                        'job_type' => $row['job_type']
                    );
                    $pdf = curl_post($apiUrl, $apiToken, http_build_query($data));

                    if ($pdf===false) {
                        $rtn = '錯誤: 未能下載服務報告!!!';
                        echo date("Y-m-d H:i:s")." Error: Unable to download PDF file!!!\n";
                    } else {
                        $fpath = '/tmp/'.Date('YmdHis').'_'.$row['job_type'].'_'.$row['job_id'].'.pdf';
                        $dname = '史伟莎服务报告.pdf';
                        if (!$fp = fopen($fpath, 'c')) {
                            $rtn = '錯誤: 未能開啟服務報告!!!';
                            echo date("Y-m-d H:i:s")." Error: Unable to open report file!!!\n";
                        } else {
                            if (fwrite($fp, $pdf)===false) {
                                fclose($fp);
                                $rtn = '錯誤: 未能覆寫服務報告!!!';
                                echo date("Y-m-d H:i:s")." Error: Unable to write report file!!!\n";
                            } else {
                                fclose($fp);

                                echo date("Y-m-d H:i:s")." ATTACH: $fpath | $dname\n";
                                $mail->attachment[$fpath] = $dname;
                                $dList[] = $fpath;

                                echo date("Y-m-d H:i:s")." Sending out ...\n";
                                $rtn = $loginacc=='1' ? $mail->sendOut() : $mail->sendOut('smtp@swisher.com.cn', '5v9tuG4U');
                            }
                        }
                    }

                    unset($mail);
                }

                $status = empty($rtn) ? 'C' : 'F';
                $info = array(
                    'to_addr' => $cust['Email'],
                    'send_dt' => Date('Y-m-d H:i:s')
                );
                markStatus($db, $row['id'], $status, $rtn, $info);
                echo date("Y-m-d H:i:s")." sendreport - Mail sent / STATUS: $status\n";

                /* Remove attachment files if disposal flag is on */
                if (!empty($dList)) {
                    foreach ($dList as $file) {
                        echo date("Y-m-d H:i:s")." sendreport - Clear file $file\n";
                        if (file_exists($file)) unlink($file);
                    }
                }
            }
        }else{
            $dList = array();
            $qid = $row['id'];
            $rtn = '';

            if (empty($cust['Email'])) {
                $rtn = '錯誤: 您必须提供至少一个收件人电子邮件地址';
                echo date("Y-m-d H:i:s")." Error: You must provide at least one recipient email address.\n";
            } else {
                $mail = new email();
                $mail->senderAddress = 'no-reply@lbsgroup.com.cn';
                $mail->senderName = 'LBS';
                $mail->recipient = json_encode(explode(',',$cust['Email']));
                $mail->ccaddress = '';
                $mail->subject = '<'.$cust['NameZH'].'> - 史伟莎服务报告';
                $mail->content = file_get_contents('./mail.html');
                echo date("Y-m-d H:i:s")." TO: ".$mail->recipient." / SUBJ: ".$mail->subject."\n";

                $data = array(
                    'staffid' => $loginId,
                    'job_id' => $row['job_id'],
                    'job_type' => $row['job_type']
                );
                $pdf = curl_post($apiUrl, $apiToken, http_build_query($data));

                if ($pdf===false) {
                    $rtn = '錯誤: 未能下載服務報告!!!';
                    echo date("Y-m-d H:i:s")." Error: Unable to download PDF file!!!\n";
                } else {
                    $fpath = '/tmp/'.Date('YmdHis').'_'.$row['job_type'].'_'.$row['job_id'].'.pdf';
                    $dname = '史伟莎服务报告.pdf';
                    if (!$fp = fopen($fpath, 'c')) {
                        $rtn = '錯誤: 未能開啟服務報告!!!';
                        echo date("Y-m-d H:i:s")." Error: Unable to open report file!!!\n";
                    } else {
                        if (fwrite($fp, $pdf)===false) {
                            fclose($fp);
                            $rtn = '錯誤: 未能覆寫服務報告!!!';
                            echo date("Y-m-d H:i:s")." Error: Unable to write report file!!!\n";
                        } else {
                            fclose($fp);

                            echo date("Y-m-d H:i:s")." ATTACH: $fpath | $dname\n";
                            $mail->attachment[$fpath] = $dname;
                            $dList[] = $fpath;

                            echo date("Y-m-d H:i:s")." Sending out ...\n";
                            $rtn = $loginacc=='1' ? $mail->sendOut() : $mail->sendOut('smtp@swisher.com.cn', '5v9tuG4U');
                        }
                    }
                }

                unset($mail);
            }

            $status = empty($rtn) ? 'C' : 'F';
            $info = array(
                'to_addr' => $cust['Email'],
                'send_dt' => Date('Y-m-d H:i:s')
            );
            markStatus($db, $row['id'], $status, $rtn, $info);
            echo date("Y-m-d H:i:s")." sendreport - Mail sent / STATUS: $status\n";

            /* Remove attachment files if disposal flag is on */
            if (!empty($dList)) {
                foreach ($dList as $file) {
                    echo date("Y-m-d H:i:s")." sendreport - Clear file $file\n";
                    if (file_exists($file)) unlink($file);
                }
            }
        }
    }
    mysqli_free_result($customer);
}
mysqli_free_result($result);

mysqli_close($db);

function markStatus(&$db, $id, $status, $mesg='', $info=[]) {
    $to = isset($info['to_addr']) ? $info['to_addr'] : '';
    $dt = isset($info['send_dt']) ? $info['send_dt'] : null;
    $sql = empty($info)
        ? sprintf("update mail_report_queue set status='%s', message='%s' where id=%s",
            $status, mysqli_escape_string($db, $mesg), $id)
        : sprintf("update mail_report_queue set status='%s', message='%s', to_addr='%s', send_dt='%s' where id=%s",
            $status, mysqli_escape_string($db, $mesg), $to, $dt, $id);
    if (!mysqli_query($db, $sql)) {
        printf("SQL Error: %s\n", mysqli_error($db));
    }
}

function curl_post($url, $key, $params) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/x-www-form-urlencoded',
        'token: '.$key
    ));
//   curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_CAINFO, "/etc/pki/tls/certs/cacert.pem");
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $out = curl_exec($ch);
    if ($out===false) {
        echo date("Y-m-d H:i:s").' Error: '.curl_error($ch);
    }
    curl_close($ch);
    return $out;
}
?>
