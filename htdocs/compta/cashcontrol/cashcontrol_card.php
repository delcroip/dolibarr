<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Charles-Fr BENKE     <charles.fr@benke.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018      Andreu Bisquerra		<jove@bisquerra.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/compta/cashcontrol/cashcontrol_card.php
 *      \ingroup    cashdesk|takepos
 *      \brief      Page to show a cash fence
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';

$langs->loadLangs(array("cashcontrol","install","cashdesk","admin","banks"));

$action=GETPOST('action','aZ09');
$id=GETPOST('id','int');
$categid = GETPOST('categid');
$label = GETPOST("label");

if (empty($conf->global->CASHDESK_ID_BANKACCOUNT_CASH) or empty($conf->global->CASHDESK_ID_BANKACCOUNT_CB)) setEventMessages($langs->trans("CashDesk")." - ".$langs->trans("NotConfigured"), null, 'errors');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield='b.label';
if (! $sortorder) $sortorder='ASC';

if (! $user->rights->cashdesk->use && ! $user->rights->takepos->use)
{
	accessforbidden();
}

$arrayofpaymentmode=array('cash'=>'Cash', 'cheque'=>'Cheque', 'card'=>'CreditCard');

$arrayofposavailable=array();
if (! empty($conf->cashdesk->enabled)) $arrayofposavailable['cashdesk']=$langs->trans('CashDesk').' (cashdesk)';
if (! empty($conf->takepos->enabled))  $arrayofposavailable['takepos']=$langs->trans('TakePOS').' (takepos)';
// TODO Add hook here to allow other POS to add themself

$cashcontrol= new CashControl($db);



/*
 * Actions
 */

if ($action=="start")
{
	$error=0;
	if (! GETPOST('posmodule','alpha') || GETPOST('posmodule','alpha') == '-1')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Module")), null, 'errors');
		$action='create';
		$error++;
	}
	if (GETPOST('posnumber','alpha') == '')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CashDesk")), null, 'errors');
		$action='create';
		$error++;
	}
	if (! GETPOST('closeyear','alpha') || GETPOST('closeyear','alpha') == '-1')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Year")), null, 'errors');
		$action='create';
		$error++;
	}
}
elseif ($action=="add")
{
	$error=0;
	if (GETPOST('opening','alpha') == '')
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("InitialBankBalance")), null, 'errors');
		$action='start';
		$error++;
	}
	foreach($arrayofpaymentmode as $key=>$val)
	{
		if (GETPOST($key,'alpha') == '')
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv($val)), null, 'errors');
			$action='start';
			$error++;
		}
		else
		{
			$cashcontrol->$key = price2num(GETPOST($key,'alpha'));
		}
	}

	if (! $error)
	{
		$cashcontrol->day_close = GETPOST('closeday', 'int');
		$cashcontrol->month_close = GETPOST('closemonth', 'int');
		$cashcontrol->year_close = GETPOST('closeyear', 'int');

	    $cashcontrol->opening=price2num(GETPOST('opening','alpha'));
	    $cashcontrol->posmodule=GETPOST('posmodule','alpha');
		$cashcontrol->posnumber=GETPOST('posnumber','alpha');

	    $id=$cashcontrol->create($user);

	    $action="view";
	}
}

if ($action=="close")
{
    $cashcontrol= new CashControl($db);
	$cashcontrol->id=$id;
    $cashcontrol->valid($user);
	$action="view";
}

if ($action=="create" || $action=="start")
{
	llxHeader();

	$initialbalanceforterminal=array();
	$theoricalamountforterminal=array();

	if (GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1')
	{
		// Calculate $initialbalanceforterminal and $theoricalamountforterminal for terminal 0
		// TODO
	}

	print load_fiche_titre($langs->trans("CashControl")." - ".$langs->trans("New"), '', 'title_bank.png');

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    if ($action == 'start' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1')
    {
	    print '<input type="hidden" name="action" value="add">';
    }
    else
    {
    	print '<input type="hidden" name="action" value="start">';
    }
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    print '<td>'.$langs->trans("Module").'</td>';
    print '<td>'.$langs->trans("CashDesk").' ID</td>';
    print '<td>'.$langs->trans("Year").'</td>';
    print '<td>'.$langs->trans("Month").'</td>';
    print '<td>'.$langs->trans("Day").'</td>';
    print '<td></td>';
    print "</tr>\n";

    $now=dol_now();
    $syear = (GETPOSTISSET('closeyear')?GETPOST('closeyear', 'int'):dol_print_date($now, "%Y"));
    $smonth = (GETPOSTISSET('closemonth')?GETPOST('closemonth', 'int'):dol_print_date($now, "%m"));
    $sday = (GETPOSTISSET('closeday')?GETPOST('closeday', 'int'):dol_print_date($now, "%d"));
	$disabled=0;
	$prefix='close';

    print '<tr class="oddeven">';
    print '<td>'.$form->selectarray('posmodule', $arrayofposavailable, GETPOST('posmodule','alpha'), (count($arrayofposavailable)>1?1:0)).'</td>';
    print '<td><input name="posnumber" type="text" class="maxwidth50" value="'.(GETPOSTISSET('posnumber')?GETPOST('posnumber','alpha'):'0').'"></td>';
	// Year
	print '<td>';
	$retstring='<select'.($disabled?' disabled':'').' class="flat valignmiddle maxwidth75imp" id="'.$prefix.'year" name="'.$prefix.'year">';
	for ($year = $syear - 10; $year < $syear + 10 ; $year++)
	{
		$retstring.='<option value="'.$year.'"'.($year == $syear ? ' selected':'').'>'.$year.'</option>';
	}
	$retstring.="</select>\n";
	print $retstring;
	print '</td>';
	// Month
	print '<td>';
	$retstring='<select'.($disabled?' disabled':'').' class="flat valignmiddle maxwidth75imp" id="'.$prefix.'month" name="'.$prefix.'month">';
	$retstring.='<option value="0"></option>';
	for ($month = 1 ; $month <= 12 ; $month++)
	{
		$retstring.='<option value="'.$month.'"'.($month == $smonth?' selected':'').'>';
		$retstring.=dol_print_date(mktime(12,0,0,$month,1,2000),"%b");
		$retstring.="</option>";
	}
	$retstring.="</select>";
	print $retstring;
	print '</td>';
	// Day
	print '<td>';
	$retstring='<select'.($disabled?' disabled':'').' class="flat valignmiddle maxwidth50imp" id="'.$prefix.'day" name="'.$prefix.'day">';
	$retstring.='<option value="0" selected>&nbsp;</option>';
	for ($day = 1 ; $day <= 31; $day++)
	{
		$retstring.='<option value="'.$day.'"'.($day == $sday ? ' selected':'').'>'.$day.'</option>';
	}
	$retstring.="</select>";
	print $retstring;
	print '</td>';
	print '<td>';
	if ($action == 'start' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1')
	{
		print '';
	}
	else
	{
		print '<input type="submit" name="add" class="button" value="'.$langs->trans("Start").'">';
	}
	print '</td>';
	print '</table>';

	if ($action == 'start' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '' && GETPOST('posnumber') != '-1')
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td align="center">'.$langs->trans("InitialBankBalance").'</td>';
		foreach($arrayofpaymentmode as $key => $val)
		{
			print '<td align="center">'.$langs->trans($val).'<br>'.$langs->trans("TheoricalAmount").'<br>'.$langs->trans("RealAmount").'</td>';
		}
		print '<td></td>';
		print '</tr>';
		print '<tr>';
		// Initial amount
		print '<td align="center"><input name="opening" type="text" class="maxwidth100" value="'.price($initialbalanceforterminal[0]).'"></td>';
		foreach($arrayofpaymentmode as $key => $val)
		{
			print '<td align="center">';
			print price($theoricalamountforterminal[0][$key]).'<br>';
			print '<input name="'.$key.'" type="text" class="maxwidth100" value="'.GETPOST($key,'alpha').'">';
			print '</td>';
		}

		print '<td align="center"><input type="submit" name="add" class="button" value="'.$langs->trans("Save").'"></td>';
		print '</tr>';
		print '</form>';
	}
    print '</form>';
}

if (empty($action) || $action=="view")
{
	$cashcontrol= new CashControl($db);
    $cashcontrol->fetch($id);
	llxHeader();
    print load_fiche_titre($langs->trans("CashControl"), '', 'title_bank.png');
    print '<div class="fichecenter">';
    print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';

	print '<tr><td class="tdfieldcreate nowrap">';
	print $langs->trans("Ref");
	print '</td><td>';
	print $id;
	print '</td></tr>';

	print '<tr><td valign="middle">'.$langs->trans("Module").'</td><td>';
	print $cashcontrol->posmodule;
	print "</td></tr>";

	print '<tr><td valign="middle">'.$langs->trans("InitialBankBalance").'</td><td>';
	print price($cashcontrol->opening);
	print "</td></tr>";

	print '<tr><td class="nowrap">';
	print $langs->trans("DateEnd");
	print '</td><td>';
	print $cashcontrol->year_close."-".$cashcontrol->month_close."-".$cashcontrol->day_close;
	print '</td></tr>';

	print '<tr><td class="nowrap">';
	print $langs->trans("Status");
	print '</td><td>';
	if ($cashcontrol->status==1) print $langs->trans("Opened");
	if ($cashcontrol->status==2) print $langs->trans("Closed");
	print '</td></tr>';

	print '</table>';
    print '</div>';

    print '<div class="fichehalfright"><div class="ficheaddleft">';
	print '<div class="underbanner clearboth"></div>';
    print '<table class="border tableforfield" width="100%">';

    print '<tr><td class="nowrap">';
    print $langs->trans("DateCreationShort");
    print '</td><td>';
    print dol_print_date($cashcontrol->date_creation, 'dayhour');
    print '</td></tr>';

	print '<tr><td valign="middle">'.$langs->trans("CashDesk").' ID</td><td>';
	print $cashcontrol->posnumber;
	print "</td></tr>";

	print "</table>\n";
    print '</div>';
    print '</div></div>';
    print '<div style="clear:both"></div>';

    dol_fiche_end();

	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction"><a target="_blank" class="butAction" href="report.php?id='.$id.'">' . $langs->trans('PrintTicket') . '</a></div>';
	if ($cashcontrol->status==1) print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&amp;action=close">' . $langs->trans('Close') . '</a></div>';
	print '</div>';

	print '<center><iframe src="report.php?id='.$id.'" width="60%" height="800"></iframe></center>';
}

// End of page
llxFooter();
$db->close();