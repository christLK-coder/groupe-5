import React, { useState, useEffect } from 'react';
import AjouterRendezVous from './AjouterRendezVous';

function App() {
    const [patients, setPatients] = useState([]);
    const [nom, setNom] = useState('');
    const [prenom, setPrenom] = useState('');
    const [email, setEmail] = useState('');
    const [telephone, setTelephone] = useState('');
    const [genre, setGenre] = useState('');

    useEffect(() => {
        const fetchPatients = async () => {
            const response = await fetch('http://localhost/backend/index.php?action=get_patients');
            const data = await response.json();
            setPatients(data);
        };
        fetchPatients();
    }, []);

    const handlePatientSubmit = async (e) => {
        e.preventDefault();
        const response = await fetch('http://localhost/backend/index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'add_patient',
                nom,
                prenom,
                email,
                telephone,
                genre,
            }),
        });
        alert(await response.text());
        setNom('');
        setPrenom('');
        setEmail('');
        setTelephone('');
        setGenre('');
    };

    return (
        <div className="container">
            <h2>Ajouter un Patient</h2>
            <form onSubmit={handlePatientSubmit} className="mb-4">
                <input className="form-control" type="text" placeholder="Nom" value={nom} onChange={(e) => setNom(e.target.value)} required />
                <input className="form-control" type="text" placeholder="Prénom" value={prenom} onChange={(e) => setPrenom(e.target.value)} required />
                <input className="form-control" type="email" placeholder="Email" value={email} onChange={(e) => setEmail(e.target.value)} required />
                <input className="form-control" type="text" placeholder="Téléphone" value={telephone} onChange={(e) => setTelephone(e.target.value)} required />
                <select className="form-control" value={genre} onChange={(e) => setGenre(e.target.value)} required>
                    <option value="">--Sélectionnez--</option>
                    <option value="Homme">Homme</option>
                    <option value="Femme">Femme</option>
                </select>
                <button className="btn btn-primary mt-2" type="submit">Ajouter</button>
            </form>

            <AjouterRendezVous />
        </div>
    );
}

export default App;