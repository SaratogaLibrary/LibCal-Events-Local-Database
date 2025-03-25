<?php

require_once 'vendor/autoload.php';
include 'bootstrap.php';

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\Style\Paper;
use PhpOffice\PhpWord\Style\Table;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use PhpOffice\PhpWord\IOFactory;

define('TMP', sys_get_temp_dir());

if ($setup) {
	// Generate the Word Document
	$tmp_name = 'room_report.docx';
	$filename = generateRoomReport($setup, $tmp_name);
	header("Expires: Mon, 1 Apr 1974 05:00:00 GMT");
	header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Pragma: no-cache");
	header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
	header("Content-Disposition: attachment; filename=\"{$filename}\"");
	header('Content-Length: ' . filesize(TMP . DIRECTORY_SEPARATOR . $tmp_name));
	@flush();
	readfile(TMP . DIRECTORY_SEPARATOR . $tmp_name);
	unlink(TMP . DIRECTORY_SEPARATOR . $tmp_name);
} else {
	die('<p>No data supplied. Please check the local database and its connection / setup, and that the proper data is being passed to this script.</p>');
}

function generateRoomReport($rooms_data, $filename) {
	// ... the FUN stuff !!!
	$doc = new PhpWord();
	Settings::setOutputEscapingEnabled(true);
	$doc->setDefaultFontName('Arial');
	$doc->getCompatibility()->setOoxmlVersion(15);

	$doc->addTitleStyle(null, ['size' => 22, 'bold' => true]);
	$doc->addTitleStyle(1, ['size' => 20, 'color' => '333333', 'bold' => true], ['alignment' => Jc::CENTER, 'spaceBefore' => 240, 'spaceAfter' => 0]);
	$doc->addTitleStyle(2, ['size' => 16, 'color' => '666666'], ['alignment' => Jc::CENTER, 'spaceBefore' => 240, 'spaceAfter' => 40]);

	$paper = new Paper();
	$paper->setSize('Letter');

	// Set Styles
	$sectionStyle = array(
		'marginTop'    => Converter::inchToTwip(.25),
		'marginRight'  => Converter::inchToTwip(.5),
		'marginBottom' => Converter::inchToTwip(.25),
		'marginLeft'   => Converter::inchToTwip(.5),
		'pageSizeW'    => $paper->getWidth(),
		'pageSizeH'    => $paper->getHeight()
	);
	$section = $doc->addSection($sectionStyle);
	$header  = $section->addHeader();
	$footer  = $section->addFooter();
	$footer->addPreserveText('{PAGE} of {NUMPAGES}', null, array('alignment' => Jc::CENTER));
	$tableStyle = array(
		'width'       => 100 * 50, // Word 2007 table width, in percentages, is measured in 50ths of a percent .....
		'unit'        => 'pct',
		'alignment'   => JcTable::CENTER,
		'borderSize'  => 5,
		'borderColor' => '333333',
		'cellMargin'  => 60,
		'layout'      => Table::LAYOUT_FIXED
	);
	$doc->addTableStyle('reportTable', $tableStyle);
	$columnHeaderStyle = array(
		'bgColor'     => 'E1E1E1',
		'valign'      => 'center'
	);
	$columnTimeHeaderStyle = array(
		'bgColor'     => 'E1E1E1',
		'valign'      => 'center',
		'width'       => 1000
	);
	$dayCellTextStyle = 'dayCellTextStyle';
	$doc->addFontStyle(
		$dayCellTextStyle, array(
			'size'       => 15,
			'bold'       => true,
			'align'      => 'center',
			'spaceAfter' => 0
		)
	);
	$stdCellStyle = array('valign' => 'center');
	$timeCellStyle = array('valign' => 'center');
	$stdCellTextStyle = 'stdCellTextStyle';
	$doc->addFontStyle(
		$stdCellTextStyle, array(
			'size'       => 12,
			'align'      => 'center',
			'spaceAfter' => 0
		)
	);
	$boldCellTextStyle = 'boldCellTextStyle';
	$doc->addFontStyle(
		$boldCellTextStyle, array(
			'size'       => 12,
			'align'      => 'center',
			'spaceAfter' => 0,
			'bold'       => true
		)
	);
	$columnHeaderTextStyle = 'columnHeaderTextStyle';
	$doc->addFontStyle(
		$columnHeaderTextStyle, array(
			'size'       => 12,
			'bold'       => true,
			'allCaps'    => true,
			'align'      => 'center',
			'spaceAfter' => 0
		)
	);
	$paragraphStyle = array(
		'align' => 'center',
		'spaceAfter' => 0
	);

	// Create the table within the Word document, and keep the data properly sorted as we go
	ksort($rooms_data);
	$lastDate  = array_keys($rooms_data);
	$firstDate = $lastDate[0];
	$lastDate  = $lastDate[(count($lastDate)-1)];
	
	foreach ($rooms_data as $date => $roomNames) {
		// Add a title for the room of the following events:
		ksort($roomNames);
		$section->addTitle(date(LONG_DATE_FORMAT, strtotime($date)), 1);


		foreach ($roomNames as $room => $booking) {
			ksort($booking);
			
			// Denote the room name for the following events:
			$section->addTitle($room, 2);

			// Begin the actual table
			$table = $section->addTable($tableStyle);
			$table->addRow(null, array('tblHeader' => true));

			// TABLE COLUMN HEADERS: | Time | Event | Owner | Setup |
			$table->addCell(600,  $columnTimeHeaderStyle)->addText('Time',     $columnHeaderTextStyle, $paragraphStyle);
			$table->addCell(1000, $columnHeaderStyle)->addText('Event',        $columnHeaderTextStyle, $paragraphStyle);
			$table->addCell(400,  $columnHeaderStyle)->addText('Contact',      $columnHeaderTextStyle, $paragraphStyle);
			$table->addCell(1000, $columnHeaderStyle)->addText('Setup / Info', $columnHeaderTextStyle, $paragraphStyle);

			foreach ($booking as $data) {
				$table->addRow(null, array('cantSplit' => true));

				// Add the remaining cells
				// TIME
				if ($data['event_start']) { // we know this is a library program
					$timeCell = $table->addCell(600, $stdCellStyle);
					$timeCellText = $timeCell->addTextRun($paragraphStyle);
					$timeCellText->addText('RESERVED:', $boldCellTextStyle);
					$timeCellText->addTextBreak();
					$timeCellText->addText(date(SHORT_TIME_FORMAT, $data['booking_start']) . '-' . date(SHORT_TIME_FORMAT, $data['booking_end']), $stdCellTextStyle);
					$timeCellText->addTextBreak();
					$timeCellText->addText('EVENT:', $boldCellTextStyle);
					$timeCellText->addTextBreak();
					$timeCellText->addText(date(SHORT_TIME_FORMAT, $data['event_start']) . '-' . date(SHORT_TIME_FORMAT, $data['event_end']), $stdCellTextStyle);
				} else {
					$table->addCell(600, $stdCellStyle)->addText(date(SHORT_TIME_FORMAT, $data['booking_start']) . '-' . date(SHORT_TIME_FORMAT, $data['booking_end']), $stdCellTextStyle, $paragraphStyle);
				}
				// EVENT
				$table->addCell(1000, $stdCellStyle)->addText($data['meeting'], $stdCellTextStyle, $paragraphStyle);
				// OWNER :: event uses `owner`, public booking uses `booking_name`
				$owner = $data['owner'] ? $data['owner'] : $data['booking_name'];
				$table->addCell(400, $stdCellStyle)->addText($owner, $stdCellTextStyle, $paragraphStyle);
				// SETUP (Equipment, Setup, and/or Notes)
				$setupCell = $table->addCell(1000, $stdCellStyle);
				$setupCellText = $setupCell->addTextRun($paragraphStyle);
				if ($data['equipment']) {
					$setupCellText->addText('EQUIPMENT: ', $boldCellTextStyle);
					$setupCellText->addText($data['equipment'], $stdCellTextStyle);
				}
				if ($data['event_start'] && $data['event_note']) {
					if ($setupCellText->getElements() > 0) {
						$setupCellText->addTextBreak();
					}
					$setup = explode("\n", $data['event_note']);
					$setupCellText->addText('SETUP: ', $boldCellTextStyle);
					foreach ($setup as $index => $line) {
						if ($index !== 0) {
							$setupCellText->addTextBreak();
						}
						$setupCellText->addText($line, $stdCellTextStyle);
					}
				} elseif ($data['event_note']) {
					if ($setupCellText->getElements() > 0) {
						$setupCellText->addTextBreak();
					}
					$setup = explode("\n", $data['event_note']);
					$text = $data['equipment'] ? ' NOTES: ' : 'NOTES: ';
					$setupCellText->addText($text, $boldCellTextStyle);
					foreach ($setup as $index => $line) {
						if ($index !== 0) {
							$setupCellText->addTextBreak();
						}
						$setupCellText->addText($line, $stdCellTextStyle);
					}
				}

			}
		}
		if ($date != $lastDate) {
			// Start each new table (day) on a new page
			$section->addPageBreak();
		}
	}

	// Set the header information based on data accumulated above
	$week_text = 'Room Report: ' . date('F j, Y', strtotime($firstDate)) . ' - ' . date('F j, Y', strtotime($lastDate));
	$header->addText($week_text, array('size' => 11, 'bold' => true), array('align' => 'center'));

	// Configure metadata
	$metadata = $doc->getDocInfo();
	$metadata->setCreator('Saratoga Springs Public Library');
	$metadata->setCompany('Saratoga Springs Public Library');
	$metadata->setTitle('Room Setup Schedule');
	$metadata->setDescription('The Room Setup Schedule for the days ' . $week_text . '.');
	$metadata->setCreated(time());
	$metadata->setModified(time());

	// Create the Word Doc
	$objWriter = IOFactory::createWriter($doc, 'Word2007');
	$objWriter->save(TMP . DIRECTORY_SEPARATOR . $filename, true);
	return $week_text . '.docx';
}