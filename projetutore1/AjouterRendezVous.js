import React, { useState, useEffect } from 'react';

function AjouterRendezVous() {
    const [patients, setPatients] = useState([]);
    const [rendezvous, setRendezvous] = useState([]);
    const [patientId, setPatientId] = useState('');
    const [date, setDate] = useState('');

    useEffect(() => {
        const fetchPatients = async () => {
            const response = await fetch('http://localhost/backend/index.php?action=get_patients');
            const data = await response.json();
            setPatients(data);
        };
        fetchPatients();
    }, []);

    useEffect(() => {
        const fetchRendezvous = async () => {
            const response = await fetch('http://localhost/backend/index.php?action=get_rendezvous');
            const data = await response.json();
            setRendezvous(data);
        };
        fetchRendezvous();
    }, []);

    const handleRendezvousSubmit = async (e) => {
        e.preventDefault();
        const response = await fetch('http://localhost/backend/index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'add_rendezvous',
                patient_id: patientId,
                date,
            }),
        });
        alert(await response.text());
        setPatientId('');
        setDate('');
    };

    const handleDeleteRendezvous = async (id) => {
        const response = await fetch('http://localhost/backend/index.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'delete_rendezvous',
                id,
            }),
        });
        alert(await response.text());
        // Reload rendezvous
        const updatedResponse = await fetch('http://localhost/backend/index.php?action=get_rendezvous');
        const updatedData = await updatedResponse.json();
        setRendezvous(updatedData);
    };

    return (
        <div>
            <h2>Ajouter un Rendez-vous</h2>
            <form onSubmit={handleRendezvousSubmit}>
                <select value={patientId} onChange={(e) => setPatientId(e.target.value)} required>
                    <option value="">--Sélectionnez un patient--</option>
                    {patients.map(patient => (
                        <option key={patient.id} value={patient.id}>{patient.nom} {patient.prenom}</option>
                    ))}
                </select>
                <input type="datetime-local" value={date} onChange={(e) => setDate(e.target.value)} required />
                <button type="submit">Ajouter Rendez-vous</button>
            </form>

            <h2>Liste des Rendez-vous</h2>
            <ul>
                {rendezvous.map(rdv => (
                    <li key={rdv.id}>
                        {rdv.nom} {rdv.prenom} - {new Date(rdv.date).toLocaleString()}
                        <button onClick={() => handleDeleteRendezvous(rdv.id)}>Supprimer</button>
                    </li>
                ))}
            </ul>
        </div>
    );
}

export default AjouterRendezVous;