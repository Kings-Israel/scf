<!DOCTYPE html>
<html>
<head>
    <title>{{ __('NOA')}}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }
        .container {
            width: 90%;
            margin: 0 auto;
        }
        .header, .footer {
            text-align: center;
            margin: 20px 0;
        }
        .header h1, .footer p {
            margin: 0;
        }
        .header h1 {
            font-size: 24px;
            color: #333;
        }
        .footer p {
            font-size: 10px;
            color: #555;
        }
        .noa_text {
          width: 100%;
          padding: 3px;
          background-color: #f4f4f4;
          color: #333;
          font-weight: bold;
          border-radius: 5px;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        caption {
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ __('NOA') }}</h1>
            <p>{{ __('Generated on')}} {{ now()->format('d M Y') }}</p>
        </div>
        <div class="noa_text">
          {!! $data !!}
        </div>
        <div class="footer">
      <p>© {{ now()->format('Y') }} YoFinvoice. {{ __('All rights reserved')}}.</p>
      <p>{{ __('For more information, visit')}}: <a href="https://www.yofinvoice.com">www.yofinvoice.com</a></p>
    </div>
    </div>
</body>
</html>
