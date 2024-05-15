import React, { useState, useEffect } from 'react';
import axios from 'axios';
import Swal from 'sweetalert2';

const ProjectList = () => {
    const [projects, setProjects] = useState([]);

    useEffect(() => {
        axios
            .get('http://localhost:8007/api/projects')
            .then((response) => {
                if (Array.isArray(response.data)) {
                    setProjects(response.data);
                } else {
                    console.error('Expected an array, but got:', response.data);
                }
            })
            .catch((error) => console.error('Error:', error));
    }, []);

    const handleEdit = (projectId) => {
        // Logic to handle edit

        console.log('Edit project with id:', projectId);
    };



    const handleDelete = (projectId) => {
        if (Swal.fire({
            title: "Are you sure?",
            text: "You won't be able to revert this!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Yes, delete it!"
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: "Deleted!",
                    text: "Your file has been deleted.",
                    icon: "success"
                });
            }
        }));
        {
            axios.delete(`http://localhost:8007/api/projects/ ${projectId}`)
                .then(() => {
                    setProjects(projects.filter((project) => project.id !== projectId));
                })
                .catch((error) => {
                    console.log('Error:', error);
                });
        }

        console.log('Delete project with id:', projectId);
    };

    return (
        <div>
            <h1>Project List</h1>
            <table>
                <thead>
                    <tr>
                        <th>Project Name</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {projects.map((project) => (
                        <tr key={project.id}>
                            <td>{project.name}</td>
                            <td>{project.description}</td>
                            <td>
                                <button onClick={() => handleEdit(project.id)}>Edit</button>
                                <button onClick={() => handleDelete(project.id)}>Delete</button>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
};

export default ProjectList;