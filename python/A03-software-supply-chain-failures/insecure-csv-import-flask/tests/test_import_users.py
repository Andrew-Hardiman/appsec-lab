from io import BytesIO

from public.app import app


def test_import_users_valid_csv_returns_200():
    # Use Flask's in-process test (HTTP) client instead of running a real server.
    client = app.test_client()

    # Build a multipart upload with an in-memory CSV file.
    data = {
        "file": (
            BytesIO(
                b"email,name,role\n"
                b"alice@example.com,Alice Admin,admin\n"
                b"bob@example.com,Bob User,user\n"
            ),
            "users.csv",
        ),
        "comments": "pytest happy path",
    }

    # Send the request through the real HTTP boundary
    response = client.post(
        "/import-users",
        data=data,
        content_type="multipart/form-data",
    )

    # Valid CSV should succeed.
    assert response.status_code == 200

    # Parse the JSON body so we can assert the response contract.
    body = response.get_json()

    # Verify the endpoint returns the expected success payload.
    assert body == {
        "message": "Import completed",
        "filename": "users.csv",
        "comments": "pytest happy path",
        "size_bytes": 82,
        "imported_count": 2,
    }


def test_import_users_missing_file_returns_400():
    # Use Flask's in-process test client.
    client = app.test_client()

    # Send the form without the required file field.
    response = client.post(
        "/import-users",
        data={"comments": "missing file test"},
        content_type="multipart/form-data",
    )

    # Missing file should be rejected as a client error.
    assert response.status_code == 400

    # Verify the API returns the expected JSON error contract.
    body = response.get_json()

    assert body == {
        "error": "CSV file is required",
    }


def test_import_users_invalid_utf8_returns_400():
    # Use Flask's in-process test client.
    client = app.test_client()

    # Upload bytes that are not valid UTF-8 for the CSV decoder.
    data = {
        "file": (
            BytesIO(b"\xff\xfe\xfa\xfb"),
            "users.csv",
        ),
        "comments": "invalid utf-8 test",
    }

    response = client.post(
        "/import-users",
        data=data,
        content_type="multipart/form-data",
    )

    # Invalid text input should be rejected as a client error.
    assert response.status_code == 400

    # Verify the API returns a controlled JSON error.
    body = response.get_json()

    assert body == {
        "error": "Uploaded file must be a UTF-8 CSV text file",
    }


def test_import_users_incorrect_headers_returns_400():
    # Use Flask's in-process test client.
    client = app.test_client()

    # Upload a CSV with the wrong header names.
    data = {
        "file": (
            BytesIO(
                b"email_address,full_name,role_name\n"
                b"alice@example.com,Alice Admin,admin\n"
            ),
            "users.csv",
        ),
        "comments": "incorrect headers test",
    }

    response = client.post(
        "/import-users",
        data=data,
        content_type="multipart/form-data",
    )

    # Incorrect headers should be rejected as a client error.
    assert response.status_code == 400

    # Verify the API returns a controlled JSON error.
    body = response.get_json()

    assert body == {
        "error": (
            "Invalid CSV headers. "
            "Missing: ['email', 'name', 'role']. "
            "Unexpected: ['email_address', 'full_name', 'role_name']."
        ),
    }


def test_import_users_invalid_row_returns_400():
    # Use Flask's in-process test client.
    client = app.test_client()

    # Upload a CSV where the first data row has an invalid email value.
    data = {
        "file": (
            BytesIO(
                b"email,name,role\n"
                b"not-an-email,Alice Admin,admin\n"
                b"bob@example.com,Bob User,user\n"
            ),
            "users.csv",
        ),
        "comments": "invalid row test",
    }

    response = client.post(
        "/import-users",
        data=data,
        content_type="multipart/form-data",
    )

    # Invalid row content should be rejected as a client error.
    assert response.status_code == 400

    # Verify the API returns the expected row-level validation error.
    body = response.get_json()

    assert body == {
        "error": (
            "Invalid CSV row at row 2. "
            "Please ensure the email address is valid."
        ),
    }
    