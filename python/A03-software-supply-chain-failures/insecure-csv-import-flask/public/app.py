from flask import Flask, jsonify, request
from werkzeug.exceptions import RequestEntityTooLarge
from src.import_users import ImportValidationError, import_users_from_csv

app = Flask(__name__)

# Limit request size for upload safety; Flask will reject larger requests with 413
app.config["MAX_CONTENT_LENGTH"] = 1 * 1024 * 1024


@app.errorhandler(RequestEntityTooLarge)
def handle_file_too_large(error):
    return jsonify({
        "error": "File too large",
        "message": "Uploaded file must be smaller than 1 MiB"
    }), 413


@app.route("/")
def index():
    return jsonify({"message": "CSV import service"})


@app.route("/import-users", methods=["POST"])
def import_users():
    uploaded_file = request.files.get("file")    
    comments = request.form.get("comments")

    if comments is None:
        comments = ""

    if uploaded_file is None:
        return jsonify({"error": "CSV file is required"}), 400
    
    filename = uploaded_file.filename
    
    file_bytes = uploaded_file.read()

    try:
        result = import_users_from_csv(file_bytes, filename, comments)
        return jsonify(result), 200
    except ImportValidationError as error:
        return jsonify({"error": str(error)}), 400

if __name__ == "__main__":
    #app.run(debug=True)
    app.run()

