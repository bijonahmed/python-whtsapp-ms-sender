<!-- resources/views/send-sms.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send SMS</title>
</head>
<body>
    <h1>Send SMS</h1>
    <form action="{{ route('send-sms') }}" method="post">
        @csrf
        <label for="phone_numbers">Phone Numbers (comma-separated):</label><br>
        <input type="text" id="phone_number" name="phone_number" required><br>
        <label for="message">Message:</label><br>
        <textarea id="message" name="message" required></textarea><br>
        <button type="submit">Send SMS</button>
    </form>
</body>
</html>
