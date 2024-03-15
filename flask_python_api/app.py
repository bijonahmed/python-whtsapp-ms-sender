from flask import Flask, request
import pywhatkit as kit

app = Flask(__name__)

@app.route('/', methods=['POST'])
def send_message():
    if request.method == 'POST':
        # Retrieve the form data from the request
        data = request.json
        phone_number = data.get('phone_number')
        message = data.get('message')

        # Print or log the received data
        print("Received phone number:", phone_number)
        print("Received message:", message)

        # Perform your desired logic here (e.g., sending WhatsApp message)
        try:
            kit.sendwhatmsg_instantly(phone_number, message)
            print(f"Message sent to {phone_number} successfully")
            # Add a newline character after sending the message
            return 'Message sent successfully!\n'
        except Exception as e:
            print(f"Failed to send message to {phone_number}: {str(e)}")
            # If message sending fails, return an error message
            return 'Failed to send message.'

if __name__ == '__main__':
    app.run(debug=True)
