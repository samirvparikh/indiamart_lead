<?php

namespace App\Console\Commands;

use App\Services\QuotationPdfService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateUserManualPdf extends Command
{
    protected $signature = 'docs:user-manual-pdf
                            {--output=docs/Torq_CRM_User_Manual.pdf : Relative path under the project root}';

    protected $description = 'Generate the Torq CRM end-user manual as a PDF';

    public function handle(QuotationPdfService $quotationPdfService): int
    {
        $relative = (string) $this->option('output');
        $path = base_path($relative);

        File::ensureDirectoryExists(dirname($path));

        $pdf = Pdf::loadView('docs.user-manual', [
            'company' => $quotationPdfService->companyProfile(),
        ])->setPaper('a4', 'portrait');

        $dompdf = $pdf->getDomPDF();
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $fontMetrics = $dompdf->getFontMetrics();
        $font = $fontMetrics->getFont('DejaVu Sans');
        $size = 9;
        $sample = 'Page 0 of 0';
        $width = $fontMetrics->getTextWidth($sample, $font, $size);
        $x = ($canvas->get_width() - $width) / 2;
        $y = $canvas->get_height() - 28;

        $canvas->page_text($x, $y, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, $size, [0.4, 0.4, 0.4]);

        File::put($path, $dompdf->output());

        $this->info('User manual PDF created: '.$path);

        return self::SUCCESS;
    }
}
