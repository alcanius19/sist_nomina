<?php
header('Content-type:application/xls');
header('Content-Disposition: attachment; filename=Nomina.xls');
include 'includes/session.php';




function generateRow($from, $to, $conn, $deduction)
{
    $sql = "SELECT *, SUM(salario_base) as salario_base , SUM(festivo) as fest ,SUM(aux_tran) as transporte , SUM(recargo_noc) as recargo , SUM(hora_ext_diu) as ext_diu, SUM(hora_ext_noc) as ext_noc, SUM(hora_ext_dom_diu) as dom_diu , sum(hora_ext_dom_noc) as dom_noc , SUM(total) as neto, attendance.employee_id AS empid, employees.employee_id AS employee FROM attendance LEFT JOIN employees ON employees.id=attendance.employee_id LEFT JOIN position ON position.id=employees.position_id WHERE date BETWEEN '$from' AND '$to' GROUP BY attendance.employee_id ORDER BY employees.lastname ASC, employees.firstname ASC";
    $query = $conn->query($sql);
    $total = 0;

    $tabla = '<table>
    <thead>
        <tr>
            <th colspan="2">Nombre Empleado</th>
            <th colspan="2">Identificacion</th>
            <th colspan="2">Salario</th>
        </tr>
    </thead>
      <tbody>';


    while ($row = $query->fetch_assoc()) {



        $empid = $row['empid'];

        $casql = "SELECT *, SUM(amount) AS cashamount FROM cashadvance WHERE employee_id='$empid' AND date_advance BETWEEN '$from' AND '$to'";


        $caquery = $conn->query($casql);
        $carow = $caquery->fetch_assoc();
        $cashadvance = $carow['cashamount'];



        $total_deduction = $deduction + $cashadvance;

        $sql1 = "SELECT *, SUM(pago) AS total_pagos FROM descansos WHERE employee_id='$empid' AND fecha_desc BETWEEN '$from' AND '$to'";
        $query1 = $conn->query($sql1);
        $row1 = $query1->fetch_assoc();
        $pagos_descansos = $row1['total_pagos'];

        $sql2 = "SELECT *, SUM(pago) AS total_otros FROM otros WHERE employee_id='$empid' AND fecha_otro BETWEEN '$from' AND '$to'";
        $query2 = $conn->query($sql2);
        $row2 = $query2->fetch_assoc();

        $sql3 = "SELECT *, SUM(pago) AS total_incapacidad FROM incapacidad WHERE employee_id='$empid' AND create_at BETWEEN '$from' AND '$to'";
        $query3 = $conn->query($sql3);
        $row3 = $query3->fetch_assoc();


        // otros

        $otros = $row2['total_otros'];
        // fin otros
        $incapacidad = $row3['total_incapacidad'];

        $pagos_descansos = $row1['total_pagos'];

        $total_deduction = $deduction + $cashadvance + $otros;
        $suma = $row['neto'] + $pagos_descansos + $incapacidad  - $total_deduction;
        $subtotal = $row['neto'];

        $total += $suma;

        $tabla .= ' <tr>
            <td colspan="2">' . $row['lastname'] . ', ' . $row['firstname'] . '</td>
            <td colspan="2">' . $row['identification'] . '</td>
            <td colspan="2">' . number_format($suma, 2) . '</td>
        </tr>';
    }

    $tabla .= '
    <tr>
				<td colspan="2" align="right"><b>SubTotal</b></td>
				<td align="right"><b>' . number_format($total, 2) . '</b></td>
			</tr>
            
			    <tr>  
           		<th colspan="2" align="center"><b>Nombre Empleado</b></th>
                <th colspan="2"align="center"><b>Cedula</b></th>
				<th colspan="2" align="center"><b>Salario Neto</b></th> 
            ';

    $sql4 = "SELECT * FROM `otros` left JOIN employees on otros.employee_id = employees.id WHERE  fecha_otro BETWEEN '$from' AND '$to'  ";
    $query4 = $conn->query($sql4);

    $total_vacaciones = 0;
    while ($row = $query4->fetch_assoc()) {
        $total_vacaciones += $row['pago'];
        $tabla .= '
			<tr>
				<td colspan="2">' . $row['lastname'] . ', ' . $row['firstname'] . '</td>
				<td colspan="2">' . $row['identification'] . '</td>
				<td colspan="2" align="right">' . number_format($row['pago'], 2) . '</td>
			</tr>
			';
    }
    $tabla .= '
	
			<tr>
				<td colspan="2" align="right"><b>Total</b></td>
				<td colspan="2" align="right"><b>' . number_format($total + $total_vacaciones, 2) . '</b></td>
			</tr>
            </tbody>
        </table>';

    return $tabla;
}

$range = $_POST['date_range'];
$ex = explode(' - ', $range);
$from = date('Y-m-d', strtotime($ex[0]));
$to = date('Y-m-d', strtotime($ex[1]));


$sql = "SELECT *, SUM(amount) as total_amount FROM deductions";
$query = $conn->query($sql);
$drow = $query->fetch_assoc();
$deduction = $drow['total_amount'];
echo generateRow($from, $to, $conn, $deduction);
$from_title = date('M d, Y', strtotime($ex[0]));
$to_title = date('M d, Y', strtotime($ex[1]));
