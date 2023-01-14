<?php

namespace App\Http\Controllers;

use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Http\Request;
use App\Models\File;
use PDF;

class OcrController extends Controller
{
    public function pdfToText($getFileName)
    {
        $fileModel = new File;
        $resultText="";
        $pdf = new \Spatie\PdfToImage\Pdf('PDF/'.$getFileName);
        $numberOfpages=$pdf->getNumberOfPages();

        for ($i=1; $i <= $numberOfpages; $i++) {
            $filename=$i."_".$getFileName.".jpg";
            $pdf->setPage($i)->saveImage('IMG/'.$filename);

            $fileModel->name = $filename;
            $fileModel->file_path = '/IMG/' . $filename;
            $ocr = new TesseractOCR();
            $ocr->image('IMG/'.$filename);

            $text=$ocr->run();
            $resultText.=$ocr->run();
            $fileModel->file_to_text = trim($text);
            $fileModel->save();
        }
        return $resultText;
    }
    public function fileUpload(Request $req){

        $req->validate([
            'file' => 'required|mimes:png,jpg,jpeg,docx,pdf|max:2048'
        ]);
        $fileModel = new File;
        if($req->file()) {
            $text="";
            $resultText="";
            if($req->file('file')->extension()=="pdf"){ //PDF FILE
                $fileName = time().'_'.$req->file->getClientOriginalName();
                $filePath = $req->file('file')->storeAs('PDF', $fileName, '');

                $resultText=$this->pdfToText($fileName);

                return back()
                ->with('success','The file has been uploaded and converted to text.(PDF)')
                ->with('text',$resultText);
            }
            if($req->file('file')->extension()=="docx"){ //DOCX FILE
                $fileName = time().'_'.$req->file->getClientOriginalName();
                $filePath = $req->file('file')->storeAs('DOCX', $fileName, '');

                $domPdfPath = base_path('vendor/dompdf/dompdf');
                 \PhpOffice\PhpWord\Settings::setPdfRendererPath($domPdfPath);
                 \PhpOffice\PhpWord\Settings::setPdfRendererName('DomPDF');
                 $Content = \PhpOffice\PhpWord\IOFactory::load(public_path('DOCX/'.$fileName));
                 $PDFWriter = \PhpOffice\PhpWord\IOFactory::createWriter($Content,'PDF');
                 $PDFWriter->save(public_path('PDF/'.$fileName.'.pdf'));

                 $resultText=$this->pdfToText($fileName.'.pdf');

                return back()
                ->with('success','The file has been uploaded and converted to text.(DOCX)')
                ->with('text',$resultText);

            }else{ //IMAGE FILES(PNG,JPEG,JPG)
                $fileName = time().'_'.$req->file->getClientOriginalName();
                $filePath = $req->file('file')->storeAs('IMG', $fileName, '');
                $fileModel->name = time().'_'.$req->file->getClientOriginalName();
                $fileModel->file_path = '/' . $filePath;
                $ocr = new TesseractOCR();
                $ocr->image($filePath);

                $text=$ocr->run();
                $fileModel->file_to_text = trim(strval($text));
                $fileModel->save();


                return back()
                ->with('success','The file has been uploaded and converted to text.')
                ->with('text',$text)
                ->with('file', $fileName);
            }
        }
   }
}
