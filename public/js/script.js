document.addEventListener('DOMContentLoaded', function() {
    const inscriptionForm = document.getElementById('inscriptionForm');
    const resultatSoumissionDiv = document.getElementById('resultatSoumission');
    const statutForm = document.getElementById('statutForm');
    const resultatStatutDiv = document.getElementById('resultatStatut');
    const convocationSection = document.getElementById('convocationSection');
    const telechargerConvocationBtn = document.getElementById('telechargerConvocationBtn');
    const resultatConvocationDiv = document.getElementById('resultatConvocation');
    const testSection = document.getElementById('testSection');
    const questionsContainer = document.getElementById('questionsContainer');
    const soumettreTestBtn = document.getElementById('soumettreTestBtn');
    const resultatTestDiv = document.getElementById('resultatTest');
    const resultatFinalSection = document.getElementById('resultatFinalSection');
    const finalResultatDiv = document.getElementById('finalResultat');
    const paiementSection = document.getElementById('paiementSection');
    const resultatPaiementDiv = document.getElementById('resultatPaiement');
    const attestationSection = document.getElementById('attestationSection');
    const telechargerAttestationBtn = document.getElementById('telechargerAttestationBtn')
    const resultatAttestationDiv = document.getElementById('resultatAttestationDiv');
    let etudiantIdGlobal = null; // Pour stocker l'identifiant de l'étudiant

    inscriptionForm.addEventListener('submit', function(event) {
        event.preventDefault();
        const formData = new FormData(inscriptionForm);

        fetch('/api/inscriptions/index.php', {
            method: 'POST',
            body: formData,
        })
        .then(response => {
            if (!response.ok) {
                // Si la réponse n'est pas OK (statut d'erreur), rejeter la promesse
                return response.text().then(text => {
                    throw new Error(`Erreur HTTP ${response.status}: ${text}`);
                });
            }
            return response.json(); // Si la réponse est OK, parser le JSON
        })
        .then(data => {
            if (data.identifiant) {
                resultatSoumissionDiv.innerText = 'Dossier soumis avec succès. Identifiant: ' + data.identifiant;
                etudiantIdGlobal = data.identifiant;
                document.getElementById('statutSection').style.display = 'block';
            } else if (data.erreur) {
                resultatSoumissionDiv.innerText = 'Erreur: ' + data.erreur;
            }
        })
        .catch(error => {
            console.error("Erreur lors de la soumission:", error); // Log l'erreur pour plus d'informations
            resultatSoumissionDiv.innerText = 'Erreur lors de la soumission du dossier: ' + error.message;
        });
    });

    window.consulterStatut = function() {
        const idEtudiant = document.getElementById('idEtudiantStatut').value;
        if (!idEtudiant) {
            resultatStatutDiv.innerText = 'Veuillez entrer l\'identifiant étudiant.';
            return;
        }
        fetch(`/api/inscriptions/?id=${idEtudiant}`)
        .then(response => response.json())
        .then(data => {
            if (data.statut) {
                resultatStatutDiv.innerText = 'Statut du dossier: ' + data.statut;
                if (data.statut === 'Accepté') {
                    etudiantIdGlobal = idEtudiant;
                    convocationSection.style.display = 'block';
                    const demarrerTestBtn = document.getElementById('demarrerTestBtn');
                    if (demarrerTestBtn) {
                        demarrerTestBtn.style.display = 'block';
                    }
                }
            } else if (data.erreur) {
                resultatStatutDiv.innerText = 'Erreur: ' + data.erreur;
            }
        })
        .catch(error => {
            resultatStatutDiv.innerText = 'Erreur réseau: ' + error;
        });
    };

    telechargerConvocationBtn.addEventListener('click', function() {
        if (etudiantIdGlobal) {
            window.location.href = `/api/inscriptions/convocations.php?id=${etudiantIdGlobal}`;
        } else {
            resultatConvocationDiv.innerText = 'Identifiant étudiant non disponible.';
        }
    });

    function demarrerTest() {
        if (etudiantIdGlobal) {
            fetch(`/api/tests/?id=${etudiantIdGlobal}&action=questions`)
            .then(response => response.json())
            .then(questions => {
                if (questions && questions.length > 0) {
                    questionsContainer.innerHTML = '';
                    questions.forEach(question => {
                        const questionDiv = document.createElement('div');
                        questionDiv.classList.add('question');
                        questionDiv.innerHTML = `<p>${question.question_text}</p><input type="text" name="reponse_${question.id}">`;
                        questionsContainer.appendChild(questionDiv);
                    });
                    testSection.style.display = 'block';
                } else if (questions && questions.erreur) {
                    resultatTestDiv.innerText = 'Erreur lors de la récupération des questions: ' + questions.erreur;
                } else {
                    resultatTestDiv.innerText = 'Aucune question de test disponible.';
                }
            })
            .catch(error => {
                resultatTestDiv.innerText = 'Erreur réseau lors de la récupération des questions: ' + error;
            });
        } else {
            resultatTestDiv.innerText = 'Identifiant étudiant non disponible pour démarrer le test.';
        }
    }
    window.demarrerTest = demarrerTest;

    soumettreTestBtn.addEventListener('click', function() {
        if (etudiantIdGlobal) {
            const reponses = {};
            document.querySelectorAll('#questionsContainer .question').forEach(questionDiv => {
                const questionIdMatch = questionDiv.querySelector('p').textContent.match(/(\d+)/);
                if (questionIdMatch && questionIdMatch[1]) {
                    const questionId = questionIdMatch[1];
                    const reponseInput = questionDiv.querySelector(`input[name="reponse_${questionId}"]`);
                    if (reponseInput) {
                        reponses[questionId] = reponseInput.value;
                    }
                }
            });

            fetch(`/api/tests/?id=${etudiantIdGlobal}&action=soumettre`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ reponses: reponses }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.score !== undefined && data.decision) {
                    resultatTestDiv.innerText = `Test soumis. Score: ${data.score}, Décision: ${data.decision}`;
                    resultatFinalSection.style.display = 'block';
                    finalResultatDiv.innerText = `Résultat final: ${data.decision} (Score: ${data.score})`;
                    if (data.decision === 'Admis') {
                        paiementSection.style.display = 'block';
                    }
                } else if (data.erreur) {
                    resultatTestDiv.innerText = 'Erreur lors de la soumission du test: ' + data.erreur;
                }
            })
            .catch(error => {
                resultatTestDiv.innerText = 'Erreur réseau lors de la soumission du test: ' + error;
            });
        } else {
            resultatTestDiv.innerText = 'Identifiant étudiant non disponible pour soumettre le test.';
        }
    });

    function obtenirResultatTest() {
        if (etudiantIdGlobal) {
            fetch(`/api/tests/?id=${etudiantIdGlobal}&action=resultat`)
            .then(response => response.json())
            .then(data => {
                if (data.statut || (data.score !== undefined && data.decision)) {
                    finalResultatDiv.innerText = `Résultat du test: ${data.decision || data.statut} (Score: ${data.score})`;
                    resultatFinalSection.style.display = 'block';
                    if (data.decision === 'Admis') {
                        paiementSection.style.display = 'block';
                    }
                } else if (data.erreur) {
                    finalResultatDiv.innerText = 'Erreur lors de la récupération du résultat: ' + data.erreur;
                }
            })
            .catch(error => {
                finalResultatDiv.innerText = 'Erreur réseau lors de la récupération du résultat: ' + error;
            });
        } else {
            finalResultatDiv.innerText = 'Identifiant étudiant non disponible pour obtenir le résultat.';
        }
    }
    window.obtenirResultatTest = obtenirResultatTest;

    window.effectuerPaiement = function() {
        if (etudiantIdGlobal) {
            const montant = document.getElementById('montantPaiement').value;
            const modePaiement = document.getElementById('modePaiement').value;
            const paiementData = {
                idEtudiant: etudiantIdGlobal,
                montant: montant,
                modePaiement: modePaiement
            };

            fetch('/api/paiement/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(paiementData),
            })
            .then(response => response.json())
            .then(data => {
                if (data.recu) {
                    resultatPaiementDiv.innerText = 'Paiement réussi. Réçu: ' + JSON.stringify(data.recu);
                    attestationSection.style.display = 'block';
                } else if (data.erreur) {
                    resultatPaiementDiv.innerText = 'Erreur de paiement: ' + data.erreur;
                }
            })
            .catch(error => {
                resultatPaiementDiv.innerText = 'Erreur réseau lors du paiement: ' + error;
            });
        } else {
            resultatPaiementDiv.innerText = 'Identifiant étudiant non disponible pour le paiement.';
        }
    };

    telechargerAttestationBtn.addEventListener('click', function() {
        if (etudiantIdGlobal) {
            window.location.href = `/api/inscriptions/attestations.php?id=${etudiantIdGlobal}`;
        } else {
            resultatAttestationDiv.innerText = 'Identifiant étudiant non disponible pour télécharger l\'attestation.';
        }
    });
});