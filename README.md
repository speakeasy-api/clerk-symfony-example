# Clerk Symfony Example

This project is an example of how to use the Clerk PHP SDK to create a Subscriber to `ControllerEvent`s and check the auth of sessions passed from a frontend.  The files of interest are `src/EventSubscriber/ApplyClerkAuthSubscriber.php` and `src/Controller/ProtectedController.php`.  This project is not optimized for production and contains a number of practices that should not be used in a production app (allow all CORS headers, no HTTPS, etc).


Install dependencies:

```bash
$ composer update
```

Make sure the CLERK_SECRET_KEY [environment variable](https://clerk.com/docs/deployments/clerk-environment-variables#clerk-publishable-and-secret-keys) is set, ie:

```bash
$ export CLERK_SECRET_KEY=my_secret_key
```

Start the server:

```bash
$ symfony server:start
```

Set `clerk.authorized_parties` in config/services.yaml:
```php
parameters:
    clerk_secret_key: '%env(CLERK_SECRET_KEY)%'
    clerk_authorized_parties: 
        - 'http://localhost:5173' # default location for the clerk react app
```

From a Clerk frontend, use the `useSession` hook to retrieve the getToken() function:

```js
const session = useSession();
const getToken = session?.session?.getToken
```

Then, request the python server:

```js
if (getToken) {
    // get the userId or null if the token is invalid
    let res = await fetch("http://localhost:8000/clerk_jwt", {
        headers: {
            "Authorization": `Bearer ${await getToken()}`
        }
    })
    console.log(await res.json()) // {userId: 'the_user_id_or_null'}

    // get gated data or a Unauthorized if the token is not valid
    res = await fetch("http://localhost:8000/get_gated", {
        headers: {
            "Authorization": `Bearer ${await getToken()}`
        }
    })
    if (res.status.ok) {
        console.log(await res.json()) // {foo: "bar"}
    } else {
        // token was invalid
    }
}
```
